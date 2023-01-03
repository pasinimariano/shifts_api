<?php

namespace App\Controller;

use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class ProfessionalController extends AbstractController
{
    private $professionalRepository;
    private $userRepository;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        ProfessionalRepository $professionalRepository,
        UserRepository $userRepository, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->professionalRepository = $professionalRepository;
        $this->userRepository = $userRepository;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/professional/{id}", name="professional_by_id", methods="GET")
     */
    public function get_professional_by_id(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $id = $request->get("id");
        $error_message = "Error when trying to get professional {$id}";

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

        try {
            $professional = $this->professionalRepository->getProfessionalById($id);

            if (is_null($professional)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Professional doesn't exist",
                ], Response::HTTP_BAD_REQUEST);
            }

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Professional successfully obtained",
                "token"=> $new_token,
                "professionals"=> $professional
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
     * @Route("/api/professional", name="all_professionals", methods="GET")
     */
    public function get_all_professionals(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to get all professionals";

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

        try {
            $professionals = $this->professionalRepository->getAllProfessionals();

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Professionals successfully obtained",
                "token"=> $new_token,
                "professionals"=> $professionals
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
