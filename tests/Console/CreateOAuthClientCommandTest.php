<?php
namespace Czim\CmsAuth\Test\Console;

use Czim\CmsAuth\Console\Commands\CreateOAuthClient;
use DB;
use Illuminate\Console\Command;
use Mockery;

class CreateOAuthClientCommandTest extends ConsoleTestCase
{

    /**
     * @test
     */
    function it_creates_a_oauth_client()
    {
        $this->artisan('cms:oauth:client', [
            'name'   => 'Test',
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
        ]);

        $this->seeInDatabase($this->prefixTable('oauth_clients'), [
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
            'name'   => 'Test',
        ]);

        $output = $this->getArtisanOutput();

        static::assertRegExp('#ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F#i', $output);
        static::assertRegExp('#iA0N09jED4Jepnk8qW8m5RvYuv6Loozj#i', $output);
    }

    /**
     * @test
     */
    function it_reports_an_error_if_the_client_id_already_exists()
    {
        DB::table($this->prefixTable('oauth_clients'))
            ->insert([
                'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
                'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
                'name'   => 'Test',
            ]);

        $this->artisan('cms:oauth:client', [
            'name'   => 'Test',
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
        ]);

        static::assertRegExp('#already exists#i', $this->getArtisanOutput());
    }

    /**
     * @test
     */
    function it_asks_confirmation_before_storing_new_client_with_existing_name()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateOAuthClient::class . '[confirm]');
        $command->shouldReceive('confirm')->once()->andReturn(true);

        $this->getConsoleKernel()->registerCommand($command);

        DB::table($this->prefixTable('oauth_clients'))
            ->insert([
                'id'     => 'akrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
                'secret' => 'aA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
                'name'   => 'Test',
            ]);

        $this->artisan('cms:oauth:client', [
            'name'   => 'Test',
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
        ]);

        $this->seeInDatabase($this->prefixTable('oauth_clients'), [
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
            'name'   => 'Test',
        ]);
    }

    /**
     * @test
     */
    function it_aborts_if_confirmation_is_denied_for_new_client_with_existing_name()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateOAuthClient::class . '[confirm]');
        $command->shouldReceive('confirm')->once()->andReturn(false);

        $this->getConsoleKernel()->registerCommand($command);

        DB::table($this->prefixTable('oauth_clients'))
            ->insert([
                'id'     => 'akrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
                'secret' => 'aA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
                'name'   => 'Test',
            ]);

        $this->artisan('cms:oauth:client', [
            'name'   => 'Test',
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
        ]);

        $this->notSeeInDatabase($this->prefixTable('oauth_clients'), [
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
            'name'   => 'Test',
        ]);
    }

    /**
     * @test
     */
    function it_warns_if_the_record_was_not_created_succesfully()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateOAuthClient::class . '[createClient]')
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('createClient')->once()
            ->with('ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F', 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj', 'Test');

        $this->getConsoleKernel()->registerCommand($command);

        $this->artisan('cms:oauth:client', [
            'name'   => 'Test',
            'id'     => 'ukrbrZe9xSz5EN5UcVgpmyhREQzQzE5F',
            'secret' => 'iA0N09jED4Jepnk8qW8m5RvYuv6Loozj',
        ]);

        static::assertRegExp('#failed to create#i', $this->getArtisanOutput());
    }

}
