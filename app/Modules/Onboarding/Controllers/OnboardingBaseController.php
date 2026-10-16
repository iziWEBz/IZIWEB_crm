<?php

namespace App\Modules\Onboarding\Controllers;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Модуль Onboarding / Навчання",
    description: "Адміністративний контур та кабінет стажера для управління базою знань і тестами"
)]
class OnboardingBaseController extends Controller
{
    // Базовий клас для наслідування інших контролерів модуля
}
