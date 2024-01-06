<?php

declare(strict_types=1);

namespace App\Utils\Validation;

use App\Entity\Languages;
use App\Utils\Constants\Status;
use App\Utils\Constants\Utils;
use App\Utils\Constants\ValidationType;
use App\Utils\Helpers\StringHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ValidationManager
{

    public function __construct(private readonly ValidatorInterface $validator, private EntityManagerInterface $entityManager, private ?Request $request = null, private array $errorMessages = [])
    {
    }

    public function setRequest(RequestStack $request_stack): void
    {
        $this->request = $request_stack->getCurrentRequest();
    }

    /**
     * @throws Exception
     */
    public function validate(string $resource): array
    {
        $validationClassName = 'App\Utils\Validation\Rules\\' . ucfirst($resource) . 'ValidationRules';
        if (!class_exists($validationClassName)) {
            dd($validationClassName);
        }

        $validationData = ValidationRules::get($resource);
        if (empty($validationData)) {
            return [];
        }

        foreach ($validationData as $key => $data) {
            $extra = !empty($data['extra']) ? $data['extra'] : [];
            $optional = !empty($data['optional']) ? $data['optional'] : [];
            $this->validateItem($data['label'], $key, $this->request->get($key), $data['rules'], $extra, $optional);
        }

        return $this->errorMessages;
    }

    private function validateItem(string $requestName, string $requestFieldName, $requestItem, array $validationType = [], array $extra = [], array $optional = []): void
    {

        $locale = "ro";
        if (class_exists('App\Entity\Languages')) {
            $defaultLanguages = $this->entityManager->getRepository(Languages::class)->findOneBy(['default' => Status::ACTIVE]);
            $firstActiveLanguages = $this->entityManager->getRepository(Languages::class)->findOneBy(['status' => Status::ACTIVE]);

            if ($defaultLanguages){
                $locale = $defaultLanguages->getLocale();
            }
            if ($firstActiveLanguages){
                $locale = $firstActiveLanguages->getLocale();
            }
        }

        foreach ($validationType as $type) {
            switch ($type) {
                case ValidationType::REQUIRED:
                    if ($requestItem !== false) {
                        $notBlankConstraint = new Assert\NotBlank();
                        $errors = $this->validator->validate($requestItem, $notBlankConstraint);
                        $errorsCount = count($errors);

                        if ($errorsCount > 0) {
                            $this->errorMessages[$requestFieldName] = Utils::FIELD_REQUIRED;
                        }

                        if (!empty($extra) && !isset($extra['min'], $extra['max'])) {
                            $errors = $this->validator->validate($requestItem, new Assert\Choice($extra));
                            $errorsCount = count($errors);

                            if ($errorsCount > 0) {
                                $this->errorMessages[$requestFieldName] = Utils::REQUIRED_CHOICE_NOT_MATCH;
                            }
                        }
                    }
                    break;
                case ValidationType::EMAIL:
                    $emailConstraint = new Assert\Email();
                    $errors = $this->validator->validate($requestItem, $emailConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::EMAIL_NOT_VALID_RFC;
                    }

                    break;
                case ValidationType::DATETIME:
                    $dateTimeConstraint = new Assert\DateTime();
                    $errors = $this->validator->validate($requestItem, $dateTimeConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::DATETIME_NOT_VALID;
                    }

                    break;
                case ValidationType::DATE:
                    $dateConstraint = new Assert\Date();
                    $errors = $this->validator->validate($requestItem, $dateConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::DATE_NOT_VALID;
                    }

                    break;
                case ValidationType::TIME:
                    $timeConstraint = new Assert\Time();
                    $requestItem = $requestItem ? $requestItem . ':00' : $requestItem;
                    $errors = $this->validator->validate($requestItem, $timeConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::TIME_HM_NOT_VALID;
                    }

                    break;
                case ValidationType::DURATION:
                    $timeConstraint = new Assert\Time();
                    $errors = $this->validator->validate($requestItem, $timeConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::TIME_HMS_NOT_VALID;
                    }

                    break;
                case ValidationType::INTEGER:
                    $regexConstraint = new Assert\Regex('/^[-+]?\d+$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_INTEGER;
                    }

                    break;
                case ValidationType::NUMERIC:
                    $integerConstraint = new Assert\Type('numeric');
                    $errors = $this->validator->validate($requestItem, $integerConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_NUMERIC;
                    }

                    break;
                case ValidationType::FLOAT:
                    $integerConstraint = new Assert\Type('float');
                    $errors = $this->validator->validate($requestItem, $integerConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_FLOAT;
                    }

                    break;
                case ValidationType::ZIP:
                    $regexConstraint = new Assert\Regex('/^\d{5}(?:[-\s]\d{4})?$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_ZIPCODE;
                    }

                    break;
                case ValidationType::BOOLEAN:
                    $identicalConstraint = new Assert\Type('bool');

                    if ($requestItem === 'true') {
                        $requestItem = True;
                    } elseif ($requestItem === 'false') {
                        $requestItem = False;
                    }

                    $errors1 = $this->validator->validate($requestItem, $identicalConstraint);
                    $errors1Count = count($errors1);

                    $choiceConstraint = new Assert\Choice(["0", "1", 0, 1]);
                    $errors2 = $this->validator->validate($requestItem, $choiceConstraint);
                    $errors2Count = count($errors2);

                    if ($errors1Count > 0 && $errors2Count > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_BOOLEAN;
                    }

                    break;
                case ValidationType::PASSWORD:
                    $regexConstraint = new Assert\Regex('/(?=.*?[0-9])(?=.*?[A-Za-z]).+/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::WEAK_PASSWORD;
                    }

                    break;
                case ValidationType::LATITUDE:
                    $regexConstraint = new Assert\Regex('/^(\+|-)?(?:90(?:(?:\.0{1,7})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,7})?))$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_DECIMAL;
                    }

                    break;
                case ValidationType::LONGITUDE:
                    $regexConstraint = new Assert\Regex('/^(\+|-)?(?:180(?:(?:\.0{1,7})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,7})?))$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_DECIMAL;
                    }

                    break;
                case ValidationType::HEX_COLOR:
                    $regexConstraint = new Assert\Regex('/#([a-fA-F0-9]{3}){1,2}\b/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_HEX_COLOR;
                    }

                    break;
                case ValidationType::URL:
                    $urlConstraint = new Assert\Url();
                    $errors = $this->validator->validate($requestItem, $urlConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_URL;
                    }

                    break;
                case ValidationType::RANGE:
                    $rangeConstraint = new Assert\Range($extra);
                    $errors = $this->validator->validate($requestItem, $rangeConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_MIN_MAX;
                    }

                    break;
                case ValidationType::CHOICE:
                    $choiceConstraint = new Assert\Choice($extra);
                    $errors = $this->validator->validate($requestItem, $choiceConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_CHOICE_NOT_MATCH;
                    }

                    break;
                case ValidationType::CONDITION:
                    if (!is_array($extra['conditionField']['value'])) {
                        $constraint = new Assert\IdenticalTo(['value' => $extra['conditionField']['value']]);
                    } else {
                        $constraint = new Assert\Choice($extra['conditionField']['value']);
                    }
                    $errorsCondition = $this->validator->validate($this->request->get($extra['conditionField']['name']), $constraint);
                    $errorsConditionCount = count($errorsCondition);

                    if ($errorsConditionCount === 0) {
                        $extraToValidate = $extra['extra'] ?? [];
                        $this->validateItem($requestName, $requestFieldName, $requestItem, $extra['rules'], $extraToValidate);
                    }
                    break;
                case ValidationType::US_DOMESTIC_PHONE:
                    $regexConstraint = new Assert\Regex('/^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_PHONE_NUMBER;
                    }

                    break;
                case ValidationType::PHONE:
                    $regexConstraint = new Assert\Regex('/^[0-9]{8,12}$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_PHONE_NUMBER;
                    }

                    break;
                case ValidationType::PHONE_MD:
                    $regexConstraint = new Assert\Regex('/^(\+?\(?373\)?(\s*\-?[0-9]\-?){8}|0?(\s*\-?[0-9]\-?){8})$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::PHONE_NUMBER_FORMAT;
                    }

                    break;
                case ValidationType::INTERNATIONAL_PHONE_OR_US:
                    $regexConstraint = new Assert\Regex('/^(\+)?[0-9]{10,11}$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_PHONE_NUMBER;
                    }

                    break;
                case ValidationType::MIN_ONE_ALTHA:
                    $regexConstraint = new Assert\Regex('/^(?=.*[a-zA-Z]).+$/');
                    $errors = $this->validator->validate($requestItem, $regexConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_MIN_ONE_ALTHA;
                    }

                    break;
                case ValidationType::LENGTH:
                    $lengthConstraint = new Assert\Length($extra);
                    $errors = $this->validator->validate($requestItem, $lengthConstraint);

                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_MIN_MAX;
                    }

                    break;
                case ValidationType::EMAILS:
                    $emailConstraint = new Assert\Email();
                    $bad_emails = [];
                    foreach (explode(',', $requestItem[0]) as $email) {
                        $email = trim($email);
                        if ($email === '') {
                            $this->errorMessages[$requestFieldName] = Utils::FIELD_EMPTY;
                            break;
                        }

                        $errors = $this->validator->validate($email, $emailConstraint);

                        if (count($errors) > 0) {
                            $bad_emails[] = $email;
                        }
                    }

                    if (count($bad_emails) > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::EMAIL_NOT_VALID_RFC;
                    }

                    break;
                case ValidationType::PHONES:
                    $regexConstraint = new Assert\Regex('/^(\+)?[0-9]{10,11}$/');
                    $phones = explode(',', $requestItem[0]);
                    $bad_phones = [];
                    foreach ($phones as $phone) {
                        if ($phone === '') {
                            $this->errorMessages[$requestFieldName] = Utils::FIELD_EMPTY;
                            break;
                        }

                        $errors = $this->validator->validate($phone, $regexConstraint);

                        if (count($errors) > 0) {
                            $bad_phones[] = $phone;
                        }
                    }

                    if (count($bad_phones) > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_PHONE_NUMBER;
                    }

                    break;
                case ValidationType::STATUS:
                    if ($requestItem) {
                        $requestArray = explode(',', $requestItem);
                        $choiceConstraint = new Assert\Choice($extra);
                        $statusErrors = [];
                        foreach ($requestArray as $value) {
                            $errors = $this->validator->validate(trim($value), $choiceConstraint);
                            $errorsCount = count($errors);
                            if ($errorsCount > 0) {
                                $statusErrors[] = trim($value);
                            }
                        }

                        if (count($statusErrors) > 0) {
                            $this->errorMessages[$requestFieldName] = Utils::REQUIRED_CHOICE_NOT_MATCH;
                        }
                    }

                    break;
                case ValidationType::ARRAY:
                    $arrayConstraint = new Assert\Type('array');
                    $errors = $this->validator->validate($requestItem, $arrayConstraint);
                    $errorsCount = count($errors);

                    if ($errorsCount > 0) {
                        $this->errorMessages[$requestFieldName] = Utils::REQUIRED_ARRAY;
                    }
                    break;
                case ValidationType::ARRAY_OF_INTEGERS:
                    if ($requestItem) {
                        $requestArray = explode(',', $requestItem);
                        $regexConstraint = new Assert\Regex('/^[-+]?\d+$/');
                        $statusErrors = [];
                        foreach ($requestArray as $value) {
                            $errors = $this->validator->validate($value, $regexConstraint);
                            $errorsCount = count($errors);
                            if ($errorsCount > 0) {
                                $statusErrors[] = trim($value);
                            }
                        }

                        if (count($statusErrors) > 0) {
                            $this->errorMessages[$requestFieldName] = Utils::REQUIRED_CHOICE_NOT_MATCH;
                        }
                    }

                    break;
                case ValidationType::ADDITIONAL_OBJECTS:
                    if ($requestItem) {
                        $requestArray = $requestItem;
                        if (isset($optional['key'])) {
                            $requestArray = [];
                            if ($optional['key'] === "default") {
                                $requestArray[$locale] = $requestItem[$locale] ?? [];
                            }else{
                                $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                            }
                        }

                        foreach ($optional['fields'] as $field => $data) {
                            if (empty($requestArray) && !isset($optional['key'])) {
                                $this->validateItem($data['label'], $requestFieldName, null, $data['rules']);
                                continue;
                            }
                            foreach ($requestArray as $index => $array) {
                                $extraMedia = !empty($data['extra']) ? $data['extra'] : [];
                                $optionalMedia = !empty($data['optional']) ? $data['optional'] : [];
                                $this->validateItem($data['label'], $requestFieldName . '.' . $index . '.' . $field, ($this->request->get($requestFieldName)[$index][$field] ?? null), $data['rules'], $extraMedia, $optionalMedia);
                            }
                        }
                    }

                    break;
                case ValidationType::TRANSLATION:
                    $requestArray = $requestItem;
                    if (isset($optional['key'])) {
                        $requestArray = [];
                        if ($optional['key'] === "default") {
                            $requestArray[$locale] = $requestItem[$locale] ?? [];
                        }else{
                            $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                        }
                    }

                    foreach ($optional['fields'] as $field => $data) {
                        if (empty($requestArray) && !isset($optional['key'])) {
                            $this->validateItem($data['label'], 'translations', null, $data['rules']);
                            continue;
                        }
                        foreach ($requestArray as $index => $array) {
                            $extraTranslation = !empty($data['extra']) ? $data['extra'] : [];
                            $optionalTranslation = !empty($data['optional']) ? $data['optional'] : [];
                            $this->validateItem($data['label'], 'translations.' . $index . '.' . $field, ($this->request->get('translations')[$index][$field] ?? null), $data['rules'], $extraTranslation, $optionalTranslation);
                        }
                    }

                    break;
                case ValidationType::MEDIA:
                    if ($requestItem) {
                        $requestArray = $requestItem;
                        if (isset($optional['key'])) {
                            $requestArray = [];
                            if ($optional['key'] === "default") {
                                $requestArray[$locale] = $requestItem[$locale] ?? [];
                            }else{
                                $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                            }
                        }

                        foreach ($optional['fields'] as $field => $data) {
                            if (empty($requestArray) && !isset($optional['key'])) {
                                $this->validateItem($data['label'], 'medias', null, $data['rules']);
                                continue;
                            }
                            foreach ($requestArray as $index => $array) {
                                $extraMedia = !empty($data['extra']) ? $data['extra'] : [];
                                $optionalMedia = !empty($data['optional']) ? $data['optional'] : [];
                                $this->validateItem($data['label'], 'medias.' . $index . '.' . $field, ($this->request->get('medias')[$index][$field] ?? null), $data['rules'], $extraMedia, $optionalMedia);
                            }
                        }
                    }

                    break;
                case ValidationType::SPECIFICATION:
                    if ($requestItem) {
                        $requestArray = $requestItem;
                        if (isset($optional['key'])) {
                            $requestArray = [];
                            if ($optional['key'] === "default") {
                                $requestArray[$locale] = $requestItem[$locale] ?? [];
                            }else{
                                $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                            }
                        }

                        foreach ($optional['fields'] as $field => $data) {
                            if (empty($requestArray) && !isset($optional['key'])) {
                                $this->validateItem($data['label'], 'specifications', null, $data['rules']);
                                continue;
                            }
                            foreach ($requestArray as $index => $array) {
                                $extraSpecification = !empty($data['extra']) ? $data['extra'] : [];
                                $optionalSpecification = !empty($data['optional']) ? $data['optional'] : [];
                                $this->validateItem($data['label'], 'specifications.' . $index . '.' . $field, ($this->request->get('specifications')[$index][$field] ?? null), $data['rules'], $extraSpecification, $optionalSpecification);
                            }
                        }
                    }

                    break;
                case ValidationType::VARIATIONS:
                    if ($requestItem) {
                        $requestArray = $requestItem;
                        if (isset($optional['key'])) {
                            $requestArray = [];
                            if ($optional['key'] === "default") {
                                $requestArray[$locale] = $requestItem[$locale] ?? [];
                            }else{
                                $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                            }
                        }

                        foreach ($optional['fields'] as $field => $data) {
                            if (empty($requestArray) && !isset($optional['key'])) {
                                $this->validateItem($data['label'], 'variations', null, $data['rules']);
                                continue;
                            }
                            foreach ($requestArray as $index => $array) {
                                $extraVariation = !empty($data['extra']) ? $data['extra'] : [];
                                $optionalVariation = !empty($data['optional']) ? $data['optional'] : [];
                                $optionalVariation['parentIndex'] = $index;
                                $this->validateItem($data['label'], 'variations.' . $index . '.' . $field, ($this->request->get('variations')[$index][$field] ?? null), $data['rules'], $extraVariation, $optionalVariation);
                            }
                        }
                    }

                    break;
                case ValidationType::FILTER_OPTIONS:
                    if ($requestItem) {
                        $requestArray = $requestItem;
                        if (isset($optional['key'])) {
                            $requestArray = [];
                            if ($optional['key'] === "default") {
                                $requestArray[$locale] = $requestItem[$locale] ?? [];
                            }else{
                                $requestArray[$optional['key']] = $requestItem[$optional['key']] ?? [];
                            }
                        }

                        foreach ($optional['fields'] as $field => $data) {
                            if (empty($requestArray) && !isset($optional['key'])) {
                                $this->validateItem($data['label'], 'filterOptions', null, $data['rules']);
                                continue;
                            }
                            foreach ($requestArray as $index => $array) {
                                $extraFilterOption = !empty($data['extra']) ? $data['extra'] : [];
                                $optionalFilterOption = !empty($data['optional']) ? $data['optional'] : [];
                                $this->validateItem($data['label'], 'variations.' . $optional['parentIndex'] . '.filterOptions.' . $index . '.' . $field, ($this->request->get('variations')[$optional['parentIndex']]['filterOptions'][$index][$field] ?? null), $data['rules'], $extraFilterOption, $optionalFilterOption);
                            }
                        }
                    }

                    break;
            }
        }
    }
}
