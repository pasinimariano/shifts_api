<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Mrsuh\JsonValidationBundle\JsonValidator\JsonValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


class UserLoginController extends AbstractController
{
    private $userRepository;
    private $validator;
    private $passwordHasher;
    private $JWTManager;

    
    public function __construct(
        UserRepository $userRepository, 
        JsonValidator $validator, 
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
    }
    
    /**
     * @Route("/api/user/login", name="user_login", methods="GET")
     */
    public function login_user(Request $request): JsonResponse
    {
        $body_content = $request->getContent();
        $error_message = "Error when trying to login user";

        $this->validator->validate($body_content, "JsonSchemas/UserLoginSchema.json");
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

            $email = $data_decoded["email"];
            $password = $data_decoded["password"];
            $user = $this->userRepository->findOneBy(["email" => $email]);

            if (is_null($user)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "User {$email} doesn't exist"
                ], Response::HTTP_BAD_REQUEST);
            }

            $validPassword = $this->passwordHasher->isPasswordValid($user, $password);

            if (!$validPassword) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Incorrect Password",
                    "va"=>$validPassword
                ], Response::HTTP_BAD_REQUEST);
            }

            $token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "token"=> $token
            ], Response::HTTP_OK);

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
