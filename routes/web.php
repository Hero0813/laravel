<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

Route::any('{any?}', function (Request $request) {
    // dd($_SERVER['REQUEST_URI']);
//   $proxyUrl = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
  $requestedUrl = "https://gorest.co.in{$_SERVER['REQUEST_URI']}";

  try {
    $client = new Client([]);
    // Handle post requests with file uploads (as multipart/form-data; no streaming)
    if ($request->isMethod('post')) {
      $formParams = $request->all();
      $fileUploads = [];
      foreach ($formParams as $key => $param) {
        if ($param instanceof UploadedFile) {
          $fileUploads[$key] = $param;
          unset($formParams[$key]);
        }
      }
      if (count($fileUploads) > 0) {
        $multipartParams = [];
        foreach ($formParams as $key => $value) {
          $multipartParams[] = [
            'name' => $key,
            'contents' => $value
          ];
        }
        foreach ($fileUploads as $key => $value) {
          $multipartParams[] = [
            'name' => $key,
            'contents' => fopen($value->getRealPath(), 'r'),
            'filename' => $value->getClientOriginalName(),
            'headers' => [
              'Content-Type' => $value->getMimeType()
            ]
          ];
        }
        $response = $client->request('POST', $requestedUrl, ['multipart' => $multipartParams]);
      } else {
        $response = $client->request('POST', $requestedUrl, [
          'form_params' => $request->all()
        ]);        
      }
    } elseif ($request->isMethod('get')) {
      $response = $client->get($requestedUrl, []);
    }

    $mimeType = $response->getHeader('Content-Type') ?: 'text/html';
    return response((string) $response->getBody())->header('Content-Type', $mimeType);
  
  } catch (HttpException $e) {
    return abort($e->getCode(), $e->getMessage());
  } catch (ServerException $e) {
    return abort($e->getCode(), $e->getMessage());
  }
})->where('any', '.*');