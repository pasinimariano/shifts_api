<?php

namespace App\Controller;

use App\Repository\ProfessionalRepository;
use App\Repository\SpecialityRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Mrsuh\JsonValidationBundle\JsonValidator\JsonValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;


class ProfessionalCreateController extends AbstractController
{
    private $professionalRepository;
    private $specialityRepository;
    private $userRepository;
    private $validator;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        ProfessionalRepository $professionalRepository, 
        SpecialityRepository $specialityRepository,
        UserRepository $userRepository,
        JsonValidator $validator, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->professionalRepository = $professionalRepository;
        $this->specialityRepository = $specialityRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/professional/create", name="professional_create", methods="POST")
     */
    public function create_professional(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to create professional";

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
            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }


        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/ProfessionalCreateSchema.json");
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
            $contact = $data_decoded["contact"];

            $this->professionalRepository->postProfessional($firstname, $lastname, $email, $contact);

            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Professional {$firstname} {$lastname} created successfully",
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
     * @Route("/api/professional/speciality", name="professional_speciality", methods="POST")
     */
    public function professional_speciality(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to add speciality to professional";

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
            
        } catch (\Exception $token_error) {
            $error = $token_error->getMessage();

            return new JsonResponse([
                "status"=> false,
                "message"=> $error_message,
                "error"=> $error,
            ], Response::HTTP_UNAUTHORIZED);
        }


        $body_content = $request->getContent();

        $this->validator->validate($body_content, "JsonSchemas/SpecialityForProfessionalSchema.json");
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

            $professional_id = $data_decoded["professional_id"];
            $spec_id = $data_decoded["spec_id"];

            $professional = $this->professionalRepository->findOneBy(["id" => $professional_id]);

            if (is_null($professional)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Professional doesn't exist"
                ], Response::HTTP_BAD_REQUEST);
            }

            $speciality = $this->specialityRepository->findOneBy(["id" => $spec_id]);

            if (is_null($speciality)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Speciality doesn't exist"
                ], Response::HTTP_BAD_REQUEST);
            }

            
            $this->professionalRepository->postSpecialityForProfessional($professional, $speciality);
            
            $spec_name = $speciality->getSpecName();
            
            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Speciality {$spec_name} added successfully.",
                "token"=> $new_token,
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
