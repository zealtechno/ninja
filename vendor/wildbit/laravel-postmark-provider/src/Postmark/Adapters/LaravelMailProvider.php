<?php namespace Postmark\Adapters;

use Illuminate\Mail\Mailer;
use Illuminate\Support\ServiceProvider;
use Swift_Mailer;

class LaravelMailProvider extends ServiceProvider {

	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton('mailer', function ($app) {
			$this->registerSwiftMailer();

			// Once we have create the mailer instance, we will set a container instance
			// on the mailer. This allows us to resolve mailer classes via containers
			// for maximum testability on said classes instead of passing Closures.
			$mailer = new Mailer(
				$app['view'], $app['swift.mailer'], $app['events']
			);

			$this->setMailerDependencies($mailer, $app);

			// If a "from" address is set, we will set it on the mailer so that all mail
			// messages sent by the applications will utilize the same "from" address
			// on each one, which makes the developer's life a lot more convenient.
			$from = $app['config']['mail.from'];

			if (is_array($from) && isset($from['address'])) {
				$mailer->alwaysFrom($from['address'], $from['name']);
			}

			return $mailer;
		});
	}

	/**
	 * Set a few dependencies on the mailer instance.
	 *
	 * @param  \Illuminate\Mail\Mailer  $mailer
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function setMailerDependencies($mailer, $app) {
		$mailer->setContainer($app);

		if ($app->bound('queue')) {
			$mailer->setQueue($app['queue.connection']);
		}
	}

	/**
	 * Register the Swift Mailer instance.
	 *
	 * @return void
	 */
	public function registerSwiftMailer() {
		$this->app['swift.mailer'] = $this->app->share(function ($app) {
			$token = $this->app['config']->get('services.postmark');
			return new Swift_Mailer(new \Postmark\Transport($token));
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['mailer', 'swift.mailer'];
	}

}