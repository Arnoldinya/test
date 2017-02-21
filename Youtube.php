<?php

namespace common\components;

use Yii;
use yii\base\Object;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use common\helpers\Variable;
use common\models\Video;
use common\models\User;

$GOOGLE_PATH =  realpath(dirname(__FILE__) . '/../../vendor/google/apiclient/src/Google');
require_once $GOOGLE_PATH . '/Client.php';
require_once $GOOGLE_PATH . '/Service.php';
require_once realpath(dirname(__FILE__) . '/../../vendor/google/apiclient-services/src/Google') . '/Service/YouTube.php';

// Класс для работы с Youtube
class Youtube extends Object
{
    public $key = '123';
    public $channelId = '123';
    
    protected $client;
    protected $youtube;
    
    private function connect()
    {
        $this->client = new \Google_Client();

        $this->client->setDeveloperKey($this->key);

        $this->youtube = new \Google_Service_YouTube($this->client);
    }
    
    public function updateVideos()
    {
        if (!isset($this->client)) 
        {
            $this->connect();
        }
        
        $pageToken = null;
        $videoResults = [];

        do
        {
            $videoIds = [];
            $searchResponse = $this->youtube->search->listSearch('id', [
                'type' => 'video',
                'channelId' => $this->channelId,
                'maxResults' => 5,
                'pageToken' => $pageToken,
            ]);
            $pageToken = $searchResponse['nextPageToken'];
            
            foreach ($searchResponse['items'] as $searchResult)
            {
                $videoIds[] = $searchResult['id']['videoId'];
            }
            
            $videoResponse = $this->youtube->videos->listVideos('snippet,statistics,contentDetails', [
                'id' => implode(',', $videoIds),
                'maxResults' => 50,
            ]);
            
            foreach ($videoResponse['items'] as $searchResult)
            {
                $videoResults[] = $searchResult;
                $model = Video::find()->where(['youtube_id' => $searchResult['id']])->one();

                if ($is_new = empty($model))
                {
                    $model = new Video([
                        'youtube_id' => $searchResult['id'],
                        'is_channel' => 1,
                        'status' => Video::STATUS_CHECKED,
                    ]);
                }

                $model->name = $searchResult['snippet']['title'];
                $model->description = $searchResult['snippet']['description'];
                $model->youtube_id = $searchResult['id'];
                $model->is_channel = 1;
                $model->status = Video::STATUS_CHECKED;
                $model->preview = $searchResult['snippet']['thumbnails']['standard']['url'];
                $model->embed = 'https://www.youtube.com/embed/'.$searchResult['id'];
                $model->url = 'http://www.youtube.com/watch?v='.$searchResult['id'];
                $model->is_show = 1;
                $model->save();

                if ($is_new)
                {
                    $model->created_at = strtotime($searchResult['snippet']['publishedAt']);
                }

                if ($model->save())
                {
                    Yii::$app->controller->stdout('Видео '.Yii::$app->controller->ansiFormat($model->name, Console::FG_GREY).' ('.
                        Yii::$app->controller->ansiFormat($model->youtube_id, Console::FG_GREY).') '.
                        ($is_new ? Yii::$app->controller->ansiFormat("добавленно\n", Console::FG_GREEN) : 
                            Yii::$app->controller->ansiFormat("обновлено\n", Console::FG_BLUE)));
                }
                else
                {
                    $errors = $model->getErrors();
                    Yii::$app->controller->stderr("Ошибка при добалении видео\n", Console::FG_RED);
                    var_dump($errors);
                }
                
            }
        } while ($pageToken != null);
    }
}
