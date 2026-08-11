<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an HTTP exception.
     *
     * A visitor who mistypes a public URL should get the school's own 404 —
     * header, footer, a way back. An admin who opens a deleted record, and an
     * API client, should not: they get Laravel's plain page and its JSON
     * respectively, which is what the parent already does.
     *
     * NOTE WHY THE SITE VIEW LIVES AT site::pages.error AND NOT IN AN `errors`
     * DIRECTORY. Laravel's RegisterErrorViewPaths maps every config('view.paths')
     * entry plus /errors into the `errors` namespace — so a file at
     * resources/site/views/errors/404.blade.php would be found as `errors::404`
     * for EVERY request, including the admin's, and there would be no way to opt
     * out of it. Keeping it out of that directory is what makes this switch
     * possible at all.
     */
    protected function renderHttpException(HttpExceptionInterface $e): Response
    {
        if ($e->getStatusCode() === 404 && $this->isSiteRequest()) {
            return response()->view('site::pages.error', ['exception' => $e], 404, $e->getHeaders());
        }

        return parent::renderHttpException($e);
    }

    /** Whether this request is for the public site rather than the admin or API. */
    protected function isSiteRequest(): bool
    {
        /** @var Request $request */
        $request = $this->container->make(Request::class);

        return ! $request->is('admin', 'admin/*', 'api/*') && ! $request->expectsJson();
    }
}
