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

      $url = "http://jlp.yahooapis.jp/MAService/V1/parse?appid=" . $apiKey . "&sentence=" . $query;

      $rss = file_get_contents($url);
      $xml = simplexml_load_string($rss);

      $word_list = [];
      $pos_list = [];
      foreach($xml->ma_result->word_list->word as $item) {
        $pos_list[] = $item->pos;
        $word_list[] = $ite->word;
      } 

      if($key = array_search('感動詞', $pos_list)) {
        $return_text = $word_list[$key];
      } elseif ($key = array_search('名詞', $pos_list)) {
        $return_text = $word_list[$key] . 'なんだー';
      }
      $bot->sendText($from, sprintf('%s', $item->pos)); 
    }
  }
  return 0;
});

$app->run();

