<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$bot = new CU\LineBot();

$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app->before(function (Request $request) use($bot) {
  // Signature validation
  $request_body = $request->getContent();
  $signature = $request->headers->get('X-LINE-CHANNELSIGNATURE');
  if (!$bot->isValid($signature, $request_body)) {
    return new Response('Signature validation failed.', 400);
  }
});

$app->post('/callback', function (Request $request) use ($app, $bot) {
  // Let's hack from here!
  $body = json_decode($request->getContent(), true);

  foreach ($body['result'] as $obj) {
    $app['monolog']->addInfo(sprintf('obj: %s', json_encode($obj)));
    $from = $obj['content']['from'];
    $content = $obj['content'];

    if ($content['text']) {
      $query = $content['text'];
      $apiKey = "dj0zaiZpPUFUaUtaWHZaQmc3QyZzPWNvbnN1bWVyc2VjcmV0Jng9NjE-";

      $url = "http://jlp.yahooapis.jp/MAService/V1/parse?appid=" . $apiKey . "&sentence=" . $query ."&response=surface,reading,pos,baseform,feature";

      $rss = file_get_contents($url);
      $xml = simplexml_load_string($rss);
      
      $pos_list = array();
      $word_list = array();
      $base_list = array();

      $i = 0;
      foreach($xml->ma_result->word_list->word as $item) {
        $pos_list[$i] = $item->pos;
        $word_list[$i] = $item->surface;
        $base_list[$i] = $item->baseform;
        $i++;
      }

     $return_text = 'うんうん'; 
      if(in_array('感動詞', $pos_list)) {
        $key = array_search('感動詞', $pos_list);
        $return_text = $base_list[$key];
      } elseif(in_array('名詞', $pos_list)) {
        $key = array_search('名詞', $pos_list);
        $return_text = $base_list[$key] . 'なんだね';
      } elseif(in_array('つらい', $base_list) || in_array('眠い', $base_list) || in_array('ねむい', $base_list) || in_array('辛い', $base_list)) {
        $return_text = '頑張ってるんだね';
      }
      $bot->sendText($from, sprintf('%s', $return_text)); 
    }
  }
  return 0;
});

$app->run();

