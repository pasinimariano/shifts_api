<?php

namespace App\Controller;

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


class SpecialityUpdateController extends AbstractController
{
    private $specialityRepository;
    private $userRepository;
    private $validator;
    private $JWTManager;
    private $JWTEncoder;

    
    public function __construct(
        SpecialityRepository $specialityRepository, 
        UserRepository $userRepository,
        JsonValidator $validator, 
        JWTTokenManagerInterface $JWTManager,
        JWTEncoderInterface $JWTEncoder)
    {
        $this->specialityRepository = $specialityRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->JWTManager = $JWTManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * @Route("/api/speciality/update", name="speciality_update", methods="PUT")
     */
    public function professional_update(Request $request): JsonResponse
    {
        $token = $request->headers->get("token");
        $error_message = "Error when trying to update speciality";

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

        $this->validator->validate($body_content, "JsonSchemas/SpecialityUpdateSchema.json");
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

            $speciality_id = $data_decoded["speciality_id"];
            $spec_name = $data_decoded["spec_name"];

            $speciality = $this->specialityRepository->findOneBy(["id" => $speciality_id]);

            if (is_null($speciality)) {
                return new JsonResponse([
                    "status"=> false,
                    "message"=> $error_message,
                    "error"=> "Speciality {$speciality_id} doesn't exist"
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $this->specialityRepository->putSpeciality($speciality, $spec_name);
            
            $new_token = $this->JWTManager->create($user);

            return new JsonResponse([
                "status"=> true,
                "message"=> "Speciality {$speciality_id} updated successfully.",
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
