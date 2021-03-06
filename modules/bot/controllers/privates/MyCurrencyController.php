<?php

namespace app\modules\bot\controllers\privates;

use app\modules\bot\components\helpers\Emoji;
use app\modules\bot\components\response\ResponseBuilder;
use app\models\Currency;
use app\modules\bot\components\helpers\PaginationButtons;
use yii\data\Pagination;
use app\modules\bot\components\Controller;

/**
 * Class MyCurrencyController
 *
 * @package app\modules\bot\controllers
 */
class MyCurrencyController extends Controller
{
    /**
     * @param null|string $currencyCode
     *
     * @return array
     */
    public function actionIndex($currencyCode = null)
    {
        $user = $this->getUser();

        $currency = null;
        if ($currencyCode) {
            $currency = Currency::findOne(['code' => $currencyCode]);
            if ($currency) {
                if ($user) {
                    $user->currency_id = $currency->id;
                    $user->save();
                }
            }
        }

        $currency = $currency ?? $user->currency;
        $currencyCode = isset($currency) ? $currency->code : null;
        $currencyName = isset($currency) ? $currency->name : null;

        return ResponseBuilder::fromUpdate($this->getUpdate())
            ->editMessageTextOrSendMessage(
                $this->render('index', compact('currencyCode', 'currencyName')),
                [
                    [
                        [
                            'callback_data' => MyProfileController::createRoute(),
                            'text' => Emoji::BACK,
                        ],
                        [
                            'callback_data' => MyCurrencyController::createRoute('list'),
                            'text' => Emoji::EDIT,
                        ],
                    ],
                ]
            )
            ->build();
    }

    /**
     * @param int $page
     *
     * @return array
     */
    public function actionList($page = 1)
    {
        $currencyQuery = Currency::find()->orderBy('code ASC');
        $pagination = new Pagination([
            'totalCount' => $currencyQuery->count(),
            'pageSize' => 9,
            'params' => [
                'page' => $page,
            ],
        ]);

        $pagination->pageSizeParam = false;
        $pagination->validatePage = true;

        $currencies = $currencyQuery->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $paginationButtons = PaginationButtons::build($pagination, function ($page) {
            return self::createRoute('list', [
                'page' => $page,
            ]);
        });
        $buttons = [];
        if ($currencies) {
            foreach ($currencies as $currency) {
                $buttons[][] = [
                    'callback_data' => self::createRoute('index', [
                        'currencyCode' => $currency->code,
                    ]),
                    'text' => strtoupper($currency->code) . ' - ' . $currency->name,
                ];
            }

            if ($paginationButtons) {
                $buttons[] = $paginationButtons;
            }

            $buttons[][] = [
                'callback_data' => self::createRoute(),
                'text' => Emoji::BACK,
            ];
        }

        return ResponseBuilder::fromUpdate($this->getUpdate())
            ->editMessageTextOrSendMessage(
                $this->render('list'),
                $buttons
            )
            ->build();
    }
}
