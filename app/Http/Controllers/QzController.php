<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * QZ Tray signing endpoints.
 *
 * Makes QZ Tray print SILENTLY (no "Allow" prompt) by cryptographically
 * signing each print request with your private key. QZ Tray verifies the
 * signature against your public certificate.
 *
 * Setup (run once on the server):
 *
 *   mkdir -p storage/app/qz
 *   openssl genrsa -out storage/app/qz/private-key.pem 2048
 *   openssl req -new -x509 -key storage/app/qz/private-key.pem \
 *       -out storage/app/qz/digital-certificate.txt -days 3650 \
 *       -subj "/CN=YourCompany/O=YourCompany/C=IN"
 *
 * Keep private-key.pem OUTSIDE the public webroot (storage/ is fine).
 *
 * Routes (routes/web.php):
 *   use App\Http\Controllers\QzController;
 *   Route::get('/qz/certificate', [QzController::class, 'certificate']);
 *   Route::post('/qz/sign',       [QzController::class, 'sign']);
 *
 * Then in the Blade view set:
 *   QZ_SIGNING.certUrl = '{{ url("/qz/certificate") }}';
 *   QZ_SIGNING.signUrl = '{{ url("/qz/sign") }}';
 */
class QzController extends Controller
{
    private function qzPath(string $file): string
    {
        return storage_path('app/qz/' . $file);
    }

    /** Return the PUBLIC certificate (safe to expose). */
    public function certificate()
    {
        $path = $this->qzPath('digital-certificate.txt');

        if (!is_file($path)) {
            return response('Certificate not found. Generate it first.', 500)
                ->header('Content-Type', 'text/plain');
        }

        return response(file_get_contents($path), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Sign the request string QZ Tray sends, using the PRIVATE key.
     * QZ (v2.1+) expects SHA512 + base64 by default.
     */
    public function sign(Request $request)
    {
        $toSign = (string) $request->input('request', '');

        $keyPem = @file_get_contents($this->qzPath('private-key.pem'));
        if ($keyPem === false) {
            return response('Private key not found.', 500)
                ->header('Content-Type', 'text/plain');
        }

        $privateKey = openssl_pkey_get_private($keyPem);
        if ($privateKey === false) {
            return response('Invalid private key.', 500)
                ->header('Content-Type', 'text/plain');
        }

        $signature = '';
        openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        // openssl_free_key() is deprecated/removed on PHP 8+, no longer needed.

        return response(base64_encode($signature), 200)
            ->header('Content-Type', 'text/plain');
    }
}
