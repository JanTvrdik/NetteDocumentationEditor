<?php
namespace App;

use Buzz;
use Github;
use Nette;


final class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$url = new Nette\Http\Url('https://github.com/login/oauth/authorize');
		$url->setQuery([
			'client_id' => $this->context->parameters['github']['clientId'],
			'redirect_uri' => $this->link('//Homepage:back'),
			'state' => Nette\Utils\Strings::random(20),
		]);
		$this->redirectUrl($url);
	}

	public function renderBack($code)
	{
		dump($this->request);
		dump($this->getHttpRequest());

		$ghParams = $this->context->parameters['github'];
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => ['Content-Type: application/x-www-form-urlencoded'],
				'content' => http_build_query([
					'client_id' => $ghParams['clientId'],
					'client_secret' => $ghParams['clientSecret'],
					'code' => $code,
				]),
			]
		]);

		$response = file_get_contents('https://github.com/login/oauth/access_token', NULL, $context);
		parse_str($response, $params);

		dump($params);



		$this->terminate();

//		$browser = new Buzz\Browser();
//		$browser->getClient()->setVerifyPeer(FALSE);
//		$response = $browser->submit('https://github.com/login/oauth/access_token', [
//			'client_id' => $ghParams['clientId'],
//			'client_secret' => $ghParams['clientSecret'],
//			'code' => $code,
//		]);
	}

	public function renderTest()
	{
		$client = new Github\Client();
		$client->authenticate('87f57d4ba367a69a2f51dfee7da370a9e7c9a434', Github\Client::AUTH_HTTP_TOKEN);
		dump($client->api('current_user')->show());
		dump($client->api('user'));

		$this->terminate();
	}

}
