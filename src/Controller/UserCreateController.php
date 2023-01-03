<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Mrsuh\JsonValidationBundle\JsonValidator\JsonValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class UserCreateController extends AbstractController
{
    private $userRepository;
    private $validator;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        UserRepository $userRepository, 
        JsonValidator $validator, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/user/create", name="user_create", methods="POST")
     */
    public function create_user(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to create user";

        if (is_null($token)) {
            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> "Token is missing"
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $token_decoded = $this->JWTEncoder->decode($token);
            $user_email = $token_decoded["username"];
            $user_role = $token_decoded["roles"];

            if ($user_role !== "ROLE_ADMIN") {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "You need to be an ADMIN"
                ], Response:: HTTP_UNAUTHORIZED);
            }

            $user = $this->userRepository->findOneBy(["email" => $user_email]);

            $new_token = $this->JWTManager->create($user);
            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }


        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/UserCreateSchema.json");
        $validator_error = $this->validator->getErrors();

        if (!empty($validator_error)) {
            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $validator_error
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data_decoded = json_decode($body_content, true);

            $firstname = $data_decoded["firstname"];
            $lastname = $data_decoded["lastname"];
            $email = $data_decoded["email"];
            $password = $data_decoded["password"];
            $role = $data_decoded["role"];

            if ($role !== "ROLE_ADMIN" and $role !== "ROLE_USER") {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Invalid role. Use: ROLE_ADMIN for Admin or ROLE_USER for User"
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->userRepository->postUser($firstname, $lastname, $email, $password, $role);

            return new JsonResponse([
                "status"=> true,
                "message"=> "User {$email} created successfully",
                "token"=> $new_token
            ], Response::HTTP_CREATED);

        } catch (\Exception $internalError) {
            $error = $internalError->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
