<?php

namespace App\Controller;

use App\Repository\SpecialityRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class SpecialityController extends AbstractController
{
    private $specialityRepository;
    private $userRepository;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        SpecialityRepository $specialityRepository,
        UserRepository $userRepository, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->specialityRepository = $specialityRepository;
        $this->userRepository = $userRepository;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/speciality/{id}", name="speciality_by_id", methods="GET")
     */
    public function get_speciality_by_id(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $id = $request->get("id");
        $error_message = "Error when trying to get speciality {$id}";

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
            $speciality = $this->specialityRepository->getSpecialityById($id);

            if (is_null($speciality)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Speciality doesn't exist",
                ], Response::HTTP_BAD_REQUEST);
            }

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Speciality successfully obtained",
                "token"=> $new_token,
                "speciality"=> $speciality
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
     * @Route("/api/speciality", name="all_specialities", methods="GET")
     */
    public function get_all_specialities(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to get all specialities";

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
            $specialities = $this->specialityRepository->getAllSpecialities();

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Specialities successfully obtained",
                "token"=> $new_token,
                "specialities"=> $specialities
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
