<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * トップページが正常に表示される。
     */
    public function test_homepage_is_available(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('都道府県の不動産取引価格をウォッチする')
            ->assertSee('LINEでお知らせ');
    }

    /**
     * 説明ページが正常に表示される。
     */
    public function test_about_page_is_available(): void
    {
        $response = $this->get('/about');

        $response
            ->assertStatus(200)
            ->assertSee('このサイトについて')
            ->assertSee('LINE通知について');
    }

    /**
     * サイトマップがXMLとして返る。
     */
    public function test_sitemap_is_available(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSee('<?xml', false);
    }
}
