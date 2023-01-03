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
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class UserUpdateController extends AbstractController
{
    private $userRepository;
    private $validator;
    private $passwordHasher;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        UserRepository $userRepository, 
        JsonValidator $validator, 
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/user/update", name="user_update", methods="PUT")
     */
    public function update_user(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to update user";

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
        
            $user = $this->userRepository->findOneBy(["email" => $user_email]);            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/UserUpdateSchema.json");
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

            $validPassword = $this->passwordHasher->isPasswordValid($user, $password);

            if (!$validPassword) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Incorrect Password"
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->userRepository->updateUser($user, $firstname, $lastname, $email);

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "User updated successfully",
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

    /**
     * @Route("/api/user/update/password", name="user_update_password", methods="PUT")
     */
    public function update_user_password(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to update user";

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
        
            $user = $this->userRepository->findOneBy(["email" => $user_email]);            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }


        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/UserUpdatePasswordSchema.json");
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

            $password = $data_decoded["password"];
            $newPassword = $data_decoded["newPassword"];

            $validPassword = $this->passwordHasher->isPasswordValid($user, $password);

            if (!$validPassword) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Incorrect Password"
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->userRepository->updatePassword($user, $newPassword);

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "User password updated successfully",
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

     /**
     * @Route("/api/user/update/role", name="user_update_role", methods="PUT")
     */
    public function update_user_role(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to update user";

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
                ], Response::HTTP_UNAUTHORIZED);
            }
        
            $user = $this->userRepository->findOneBy(["email" => $user_email]);            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/UserUpdateRoleSchema.json");
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

            $new_role = $data_decoded["role"];
            $password = $data_decoded["password"];
            $user_for_update_id = $data_decoded["userForUpdateId"];

            if ($new_role !== "ROLE_ADMIN" and $new_role !== "ROLE_USER") {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Invalid role. Use: ROLE_ADMIN for Admin or ROLE_USER for User"
                ], Response::HTTP_BAD_REQUEST);
            }

            $validPassword = $this->passwordHasher->isPasswordValid($user, $password);

            if (!$validPassword) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Incorrect Password"
                ], Response::HTTP_BAD_REQUEST);
            }

            $user_for_update = $this->userRepository->findOneBy(["id" => $user_for_update_id]);

            if (is_null($user_for_update)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "User {$user_for_update_id} doesn't exist"
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->userRepository->updateRole($user_for_update, $new_role);

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "User role updated successfully",
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
