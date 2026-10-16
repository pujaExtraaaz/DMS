<?php

namespace Tests\Feature\Support;

use Illuminate\Http\Request;
use App\Http\Middleware\UppercaseNameInputs;
use Tests\TestCase;

class UppercaseNameInputsMiddlewareTest extends TestCase
{
    public function test_middleware_uppercases_name_fields_in_request(): void
    {
        $middleware = new UppercaseNameInputs();

        $request = Request::create('/test', 'POST', [
            'name' => 'Sony professional Display',
            'legal_name' => 'avit digital',
            'transporter_name' => 'Bulk Foods Depot',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'nested' => [
                'name' => 'john smith',
                'sku' => 'sku-xyz',
            ],
            'items' => [
                ['name' => 'item one', 'qty' => 5],
                ['name' => 'item two', 'qty' => 10],
            ],
        ]);

        $response = $middleware->handle($request, function ($req) {
            $this->assertSame('SONY PROFESSIONAL DISPLAY', $req->input('name'));
            $this->assertSame('AVIT DIGITAL', $req->input('legal_name'));
            $this->assertSame('BULK FOODS DEPOT', $req->input('transporter_name'));
            $this->assertSame('admin@example.com', $req->input('email'));
            $this->assertSame('secret123', $req->input('password'));
            $this->assertSame('JOHN SMITH', $req->input('nested.name'));
            $this->assertSame('sku-xyz', $req->input('nested.sku'));
            $this->assertSame('ITEM ONE', $req->input('items.0.name'));
            $this->assertSame('ITEM TWO', $req->input('items.1.name'));
            $this->assertSame(5, $req->input('items.0.qty'));

            return response('OK');
        });

        $this->assertSame('OK', $response->getContent());
    }
}

