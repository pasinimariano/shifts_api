<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class UserController extends AbstractController
{
    private $userRepository;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        UserRepository $userRepository, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->userRepository = $userRepository;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/user", name="all_user", methods="GET")
     */
    public function get_all_users(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to get all users";

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

        try {
            $users_find_all = $this->userRepository->findAll();
            $users = array_map(function($user) { 
                $response = [
                    "id"=> $user->getId(),
                    "firstname"=> $user->getFirstname(),
                    "lastname"=> $user->getLastname(),
                    "email"=> $user->getEmail(),
                    "role"=> $user->getRoles()
                ];

                return $response;
            }, $users_find_all);

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Users successfully obtained",
                "token"=> $new_token,
                "users"=> $users
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
