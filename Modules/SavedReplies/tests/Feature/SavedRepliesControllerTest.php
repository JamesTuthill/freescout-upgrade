<?php

namespace Modules\SavedReplies\tests\Feature;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\App;

class SavedRepliesControllerTest extends TestCase
{

    protected function setUp(): void {
        //MailBox
        factory(App\Mailbox::class, 1)->make([
            'name' => 'primary_mailbox',
            'id' => 1
        ]);

        factory(App\Mailbox::class, 1)->make([
            'id' => 2
        ]);

        //SavedReply
        factory(Modules\SavedReplies\Entities\SavedReply::class, 1)->make([
            'mailbox_id' => 1,
            'name' => 'Aereth Gainsborough',
            'text' => 'This is the body of the reply'
        ]);

        factory(Modules\SavedReplies\Entities\SavedReply::class, 1)->make([
            'mailbox_id' => 2,
            'name' => 'Leon S Kennedy'
        ]);

        //User
        factory(App\User::class, 1)->states('admin','does_not_have_saved_reply_permission')->make([
            'id' => 1
        ]);
    }

    public function testAdminCanSeeSavedReplies()
    {
        //GIVEN
        $this->setUp();
        $user = App\User::where('first_name','admin')->first();

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/1');

        //THEN
        $response->assertSee('new-saved-reply');
        $response->assertSee('saved-reply-1');
        $response->assertSee('<span>Aereth Gainsborough</span>');
        $response->assertSee('This is the body of the reply');

        $response->assertDontSeeText('A saved reply is a snippet of text');
        $response->assertDontSeeText('<span>Leon S Kennedy</span>');
    }

    public function testUserWithPermissionAndAccessCanSeeSavedReplies(){
        //GIVEN
        $this->setUp();

        factory(App\User::class, 1)->states('not_admin','has_saved_reply_permission')->make([
            'id' => 2
        ]);
        factory(App\MailboxUser::class, 1)->make([
            'mailbox_id' => 1,
            'user_id' => 2
        ]);

        $user = App\User::where('first_name','not_admin')->first();

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/1');

        //THEN
        $response->assertSuccessful();

    }

    public function testUserWithoutPermissionOrAccessCannotSeeSavedReplies(){
        //GIVEN
        $this->setUp();

        factory(App\User::class, 1)->states('not_admin','does_not_have_saved_reply_permission')->make([
            'id' => 2
        ]);

        $user = App\User::where('first_name','not_admin')->first();

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/1');

        //THEN
        $response->assertStatus(403);
    }

    public function testUserWithPermissionButNotAccessCannotSeeSavedReplies(){
        //GIVEN
        $this->setUp();

        factory(App\User::class, 1)->states('not_admin','has_saved_reply_permission')->make([
            'id' => 2
        ]);

        $user = App\User::where('first_name','not_admin')->first();

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/1');

        //THEN
        $response->assertStatus(403);
    }

    public function testUserWithAccessButNotPermissionCannotSeeSavedReplies(){
        //GIVEN
        $this->setUp();

        factory(App\User::class, 1)->states('not_admin','does_not_have_saved_reply_permission')->make([
            'id' => 2
        ]);
        factory(App\MailboxUser::class, 1)->make([
            'mailbox_id' => 1,
            'user_id' => 2
        ]);

        $user = App\User::where('first_name','not_admin')->first();

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/1');

        //THEN
        $response->assertStatus(403);
    }

    public function testAttatchmentsAppear(){

    }

    public function testCategoriesAppear(){

    }

    public function testGlobalValueBehavior(){

    }

    public function testNoSavedRepliesFound(){
        //GIVEN
        $user = App\User::where('first_name','admin')->first();

        factory(App\Mailbox::class, 1)->make([
            'name' => 'dummy_mailbox',
            'id' => 3
        ]);

        //WHEN
        $response = $this->actingAs($user)
            ->get('/mailbox/saved-replies/3');

        //THEN
        $response->assertSee('empty-content');
    }

    public function testSendQuickReply(){
        //GIVEN
        $user = App\User::where('first_name','admin')->first();

        $mock = new MockHandler([
            new Response(200, ['status' => 'success'], 'Hello, World'),
        ]);

        App::bind('httpClient', function() use ($mock) {
            return $mock;
        });

        //WHEN
        $response = $this->actingAs($user)
            ->post('/mailbox/saved-replies/quick-reply/5',
                [
                    'title' => 're: your reply',
                    'body' => 'blah blah blah blah'
                ]
            );

        //THEN
        $response->assertStatus(200);
        $response->assertSee('re: your reply - was sent');
    }
}
