<?php

namespace Database\Seeders;

use App\Models\alarm\AlarmTemplate;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

class AlarmTemplateSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (AlarmTemplate::all()->isNotEmpty()) {
      return;
    }

    $data = [
      [
        'id' => 'stadium-before-league-open',
        'title' => '{{league}} OPEN',
        'route' => '{"id": "{{league_id}}"}',
        'message' => [
          'en' => "The {{league}} starts in {{dday}} days. Try card upgrades or join the game.",
          'ko' => "{{league}} 시작 D-{{dday}}입니다. 카드 강화 또는 게임에 참여해보세요.",
        ],
        'description' => '리그 개막 전',
      ],
      [
        'id' => 'stadium-league-open',
        'title' => '{{league}} OPEN',
        'route' => '{"id": "{{league_id}}"}',
        'message' => [
          'en' => "The {{league}} has kicked off. Join various {{league}}-related games now.",
          'ko' => "{{league}}가 개막하였습니다. {{league}} 관련 다양한 게임에 참여해보세요.",
        ],
        'description' => '리그 개막',
      ],
      [
        'id' => 'stadium-game-new',
        'title' => 'Register a new game',
        'route' => '{"id": {{game_id}}}',
        'message' => [
          'en' => "{{league}} Game {{round}} registered.",
          'ko' => "{{league}} Game {{round}}이 등록되었습니다.",
        ],
        'description' => '신규 게임 등록',
      ],
      [
        'id' => 'stadium-game-start',
        'title' => 'Game {{round}} Start',
        'route' => '{"id": {{game_id}}}',
        'message' => [
          'en' => "Game {{round}} has started. Check out the game in real-time.",
          'ko' => "Game {{round}}이 시작되었습니다. 실시간으로 게임을 확인해보세요.",
        ],
        'description' => '참가 게임 시작',
      ],
      [
        'id' => 'stadium-game-end',
        'title' => 'Game {{round}} Ended',
        'route' => '{"id": {{game_id}}}',
        'message' => [
          'en' => "Game {{round}} has ended. Check the rankings and rewards right now.",
          'ko' => "Game {{round}}이 종료되었습니다. 지금 바로 순위와 보상을 확인해보세요.",
        ],
        'description' => '참가 게임 종료',
      ],
      [
        'id' => 'market-buy-complete',
        'title' => 'Market',
        'message' => [
          'en' => "Your bid on {{player_name}} card has been completed. Please check the bidding results.",
          'ko' => "입찰한 {{player_name}}카드에 대한 판매가 완료되었습니다. 입찰 결과를 확인해보세요.",
        ],
        'description' => '카드 입찰 결과',
      ],
      [
        'id' => 'market-buy-failed',
        'title' => 'Market',
        'message' => [
          'en' => "You have failed in {{player_name}} card's bid. Please try bidding again.",
          'ko' => "{{player_name}} 입찰에 실패하였습니다. 다시 입찰에 도전해보세요.",
        ],
        'description' => '카드 입찰 실패',
      ],
      [
        'id' => 'market-sell-complete',
        'title' => 'Market',
        'message' => [
          'en' => "{{player_name}} card sale has been completed. Check the sales history.",
          'ko' => "{{player_name}} 카드 판매가 완료되었습니다. 판매내역을 확인해보세요.",
        ],
        'description' => '카드 판매 완료',
      ],
      [
        'id' => 'market-sell-expired',
        'title' => 'Market',
        'message' => [
          'en' => "{{player_name}} card sale failed because there were no bidders. Please try again.",
          'ko' => "입찰자가 없어 {{player_name}} 카드 판매에 실패하였습니다. 다시 시도해주세요.",
        ],
        'description' => '카드 판매 만료',
      ],
      [
        'id' => 'draft-card-upgraded',
        'title' => 'Draft',
        'message' => [
          'en' => "You have upgraded cards. Check your upgraded cards.",
          'ko' => "강화 완료된 카드가 있습니다. 강화 완료된 카드를 확인해보세요.",
        ],
        'description' => '카드 강화 완료',
      ],
      [
        'id' => 'community-notice-new',
        'title' => 'Notice',
        'route' => '{"id": {{post_id}}}',
        'message' => [
          'en' => "A new notice has been posted.",
          'ko' => "새로운 공지가 등록되었습니다.",
        ],
        'description' => '공지사항 등록',
      ],
      [
        'id' => 'community-comment-new',
        'title' => 'Community',
        'route' => '{"id": {{post_id}}}',
        'message' => [
          'en' => "There are [{{comment_cnt}}] comments on [{{title}}].",
          'ko' => "[{{title}}]에 댓글[{{comment_cnt}}]이 달렸습니다.",
        ],
        'description' => '댓글 등록',
      ],
      [
        'id' => 'community-comment-reply',
        'title' => 'Community',
        'route' => '{"id": {{post_id}}}',
        'message' => [
          'en' => "{{user_name}} has replied to your comment.",
          'ko' => "{{user_name}}님이 회원님의 댓글에 답글을 달았습니다.",
        ],
        'description' => '대댓글 등록',
      ],
      [
        'id' => 'lineup-player-warning',
        'title' => 'Lineup',
        'message' => [
          'en' => "some player`s team has been changed on your lineup",
          'ko' => "게임에 등록된 라인업의 특정 선수의 팀이 변경되었습니다.",
        ],
        'description' => '라인업 선수 팀 변경',
      ],
    ];

    DB::beginTransaction();
    try {
      foreach ($data as $row) {
        AlarmTemplate::updateOrCreateEx(['id' => $row['id']], $row);
      }
      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      dd($th);
    }
  }
}
