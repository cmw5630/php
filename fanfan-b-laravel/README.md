# 시뮬레이션
### 시간 정책(서버 시간대 기준)
- 경기 진행 : 월요일 10시 ~ 토요일 20시(매 정시)
- 집계(랭크, 승격/강등) 시간 : 토요일 22시
- 스케쥴 생성 시간 : 일요일 05시
- 시즌 오픈 시간 : 일요일 10시

# 커맨드

### pullopta 사용법 🤠
- `php artisan pullopta —list`  (파서 클래스 이름 => feedNick) 정보
- `php artisan pullopta OT2 TM1` (피드닉을 넘겨서 파서를 실행, 파서 실행 순서는 아규먼트로 넘겨진 순서가 아닌 fantasy_metas의 sync_order에 따른다. —act=false가 기본값이므로 OT2에 대해서 데이터 전처리 로그만 찍히고 종료됨.)
- `php artisan pullopta OT2 TM1 —mode=daily` (—mode를 설정가능, 기본 all)
- `php artisan pullopta OT2 TM1 BABO` (BABO는 피드닉 목록에 없으므로 파싱 안됨)
- `php artisan pullopta` (전체 파싱, 기존 방식 동일)
- `php artisan pullopta —mode=daily` (daily 모드로 전체 파싱)
- `php artisan pullopta OT2 TM1 —act=true` (OT2를 start(true)로 파싱, —act=false가 기본값)

### syncgroup 사용법 😎
- `php artisan syncgroup daily`
- `php artisan syncgroup all`
- `daily나 all은 fantasy_metas` 테이블의 sync_group


### 플레이트 카드 가격 변동 로직 실행
- 그냥 재수집 : `php artisan platecard --type=pricechange --mode=reset`
- 현재 시즌 reset 후 로직 재실행 : `php artisan platecard --type=pricechange`

### 게임 만들기
- 만들기: `postman(Admin) - saveGame`, `api method - makeGame` 
- 지우기: `postman(Admin) - cancelGame`, `api method - cancelGame`
- 파라미터는 적절하게

### 재강화관련
- `php artisan draftagain --gameid=5`
- `php artisan draftagain --scheduleid=c4udtbpam0or0q6jjegv34ydw` (게임스케쥴에 포함된 경기만 가능)
- `--gameid` , `--scheduleid` 두 옵션을 같이 적용할 수 없음
- 재강화만 가능 `upgrading` 중인 카드는 적용 불가


# Live Logic
- 경기 종료 상태(`schedule_status`)에 따라 마무리 로직이 다를 수 있다. 3가지의 `wrapUp` 메서드 존재(그중 `wrapUpCommon`은 다른 2개의 `wrapUp`에서 공통적으로 호출된다.) 
1. `wrapUpNormal` : 정상종료(`Played`, `Awarded`) 시 호출
3. `wrapUpCancel` : `Cancelled`, `PostPhoned`, `Suspended` 시 종료 작업
4. `wrapUpCommon` : 공통 종료 작업
# User Plate Card Lock
- **인게임(lineup) 제출**과 **Market 등록**처럼 서로 동시에 진행할 수 없는 작업을 컨트롤 하기 위해 `lock_staus` 를 관리하기 위해 헬퍼함수를 사용.
- 단순 체크 `__canAccessUserPlateCardWithLock`로 파라미터로 전달된 **lock type**으로 접근 여부 체크 
- 작업 시작시 `__startUserPlateCardLock` 접근여부 체크 + `lock_status`를 전달된 **lock type**으로 변경 
- 작업 종료시 `__endUserPlateCardLock`으로 진행중인 작업의 `lock_status`를 null 초기화 한다.
# 스케쥴러
## 기본 opta 데이터 수집
### syncgroup all
#### 실행 : `php artisan sync-group all`
#### 조건 : 
- 다음 테이블들에 이전 시즌들에 대한 집계데이터가 입력되어 있어야 한다.
- 현재 진행중인 시즌을 위한 집계데이터는 수집 시에 자동으로 계산될 수 있다.
- 전 시즌을 위한 데이터는 반드시 미리 준비되어야 한다.
- 데이터 팀에서 분석한 값을 직접 입력도 가능
- 조건의 세부 사항은 각 테이블 마다 조금씩 다를 수 있다.
1. `ref_platec_quantiles` (카드 가격 초기화를 위해 필요, 현재 시즌을 위한 데이터만 있으면 됨.)
2. `ref_pointc_quantiles` (종료된 시즌 수집 및 live(현재시즌)) ingame card_grade 계산을 위한 card_c 계산을 위함. (전시즌까지의 집계데이터는 미리 준비되어야 현재시즌을 위한 집계가 자동계산됨)
3. `ref_cardc_quantiles` (기존 시즌 수집 및 live) ingame card_grade 계산을 위함. (2번과 마찬가지로 전시즌을 위한 집계데이터가 수집 전에 미리 준비되어야함.)
4. `ref_power_ranking_quantiles` 미리 준비가 안되어 있어도 수집에 문제는 없지만 카드 초기화가 진행되지 않는다.
5. `ref_team_tier_bonuses` 는 새로운 시즌 시작 시에 항상 미리 준비되어 있어야한다.

#### 관리 테이블
- `fantasy_metas` : 수집이 도중에 멈춘 경우 멈춘 부분의 `active`을 `no`로 초기화 후 재수집이 필요하다. 
#### 파싱 에러 처리
- `Opta Request Error`: 요청시 발생하는 에러다. 그 중 `schedules` 수집시 발생하는 에러는 `cURL error 6: Could not resolve host:curl`인 경우에는 만약 최초 수집이라면 재수집이 필요하다. 시즌 시작 전까지 여러번 수집하게 되므로 서버 세팅 후 최초 수집시에만 신경쓰면 된다.

# Ref~ 테이블과 soccer_preset DB 설명
  - **ref_avg_fps** - [관련class](app/Console/Commands/DataControll/FpCategoryAverageRefUpdator.php#L76)
    - source - **_player_daily_stats_** (`summary_position`, `fantasy_point`)
    - 사용 - 단순 참조 정보
    - seed 여부 - 
    - 설명 - season 별, 포지션 별 판타지 포인트 평균
  - **ref_cardc_quantiles** - [관련class](app/Console/Commands/DataControll/CardCQuantileUpdator.php#L90)
    - source - **_player_daily_stats_** (`summary_position`, `card_c`)
    - 사용 - **MA2 파서**에서 사용 **_player_daily_stats_** 의 `card_grade`를 계산하기 위한 참조(quantile) 테이블
    - seed 여부 - 
    - 설명 - _season_ 별 _summary_position_ 별 `card_c` qauntile 
  - **ref_draft_prices** - 
    - source - X
    - 사용 - **Admin**
    - seed 여부 - O 
    - 설명 - 강화 가격 설정 테이블
  - **ref_league_tiers** - 
    - source - X
    - 사용 - 플레이트 카드 가격 초기화
    - seed 여부 - O 
    - 설명 - 
  - **ref_plate_c_players** - [관련class](app/Console/Commands/DataControll/PlateCRefsUpdator.php#L70)
    - source - **_opta_player_daily_stats_** (`power_ranking`, `game_started`, `total_sub_on`, `player_id`)
    - 사용 - 플레이트 카드 가격 초기화 프로세스
    - seed 여부 -
    - 설명 - **_opta_player_daily_stats_** 테이블의 플레이어의 선수에 대한 **plate_c(avg_pw + game_d)** 계산을 위한 (집계)참조 테이블
  - **ref_plate_c_quantiles**- [관련class](app/Console/Commands/DataControll/PlateCRefsUpdator.php#L156)
    - source - **_ref_plate_c_players_** (`league_id`, `source_season_id`, `plate_c`, `nrank`), **_ref_plate_grade_prices_** (`quantile`, `grade`)
    - 사용 - 플레이트 카드 가격 초기화 프로세스(플레이트 카드 가격 등급)
    - seed 여부 - O
    - 설명 - **_ref_plate_c_players_** 테이블에서 `plate_c`의 _season_ 별 quantile을 구한다. (quantile point는 **_ref_plate_grade_prices_** 를 참조한다.)
  - **ref_plate_grade_prices** - 
    - source - X
    - 사용 -
    - seed 여부 - O
    - 설명 - 플레이트 카드 `price`, `grade`, `percentile_point` 참조 테이블
  - **ref_player_current_metas** - [관련class](app/Console/Commands/DataControll/PlayerCurrentMetaRef.php)
    - source - **_opta_player_daily_stat_** , **_plate_cards_**
    - 사용 -
    - seed 여부 - X
    - 설명 - 
  - **ref_player_season_strengths** - [관련class](app/Console/Commands/DataControll/PlayerStrengthRefUpdator.php)
    - source - 
    - 사용 - 보류
    - seed 여부 - X
    - 설명 - 
  - **ref_pointc_quantiles** - [관련class](app/Console/Commands/DataControll/PointCQuantileUpdator.php#L126)
    - source - **_player_daily_stats_** (`fantasy_point`, `summary_position`)
    - 사용 - card_c 계산을 위함.
    - seed 여부 -
    - 설명 - 이전 3개 시즌에 대해 `season_name` 타입(double, single) 별, `summary_position` 별로  `fantasy_point`의 _quatile_ 을 구한 테이블
  - **ref_power_ranking_quantiles** - 
    - source - X
    - 사용 - 
    - seed 여부 - O
    - 설명 - plate card price 변동 로직에 사용됨. `league` 별 `power_ranking` 의 `mean`, `stdev`, `normalized_value`
  - **ref_price_grade_transform_maps** - 
    - source - X
    - 사용 - 
    - seed 여부 - O
    - 설명 - plate card price 변동 로직에 사용됨. 변동 참조 map
  - **ref_team_tier_bonuses** - 
    - source - X
    - 사용 - 현재 plate card price 초기화 프로세스에 사용. - 사용 안할 예정
    - seed 여부 - O
    - 설명 - 
  - **ref_transfer_values** - 
    - source - X
    - 사용 - 현재 plate card price 초기화 프로세스에 사용. - 사용 안할 예정
    - seed 여부 - O
    - 설명 - 

# laravel 서버 Supervisor 설정 
## 기본 실행방법
- 프로젝트 루트 디렉토리에서 `source startsv.sh` 실행.
- **dev**, **production** 에서 동일 사용.

## 주요파일
- `startsv.sh`
- `supervisord.conf`
- 프로젝트별로 필요한 작업 정의 conf 파일 - 예) `supervisor_soccer.conf` - 이름은 프로젝트별로 달라야한다.(이름 형식 아래 **_주의할 점_** 참고)
## **supervisor**에 **프로젝트 별로 작업 추가** 방법 
- 별도로 `supervisor_*.conf` 파일이름 형식으로 `supervisor_` Prefix를 붙여  고유한 파일이름으로 conf 파일 생성 후 작업추가. 
- **_주의할 점_** - `supervisor_*.conf` 파일 내에 `[program:SoccerQueueJob]` 작업이름을 만들 때 프로젝트별 prefix를 붙여 작업이름이 겹치지 않도록 한다.
- 최초 적용 작업 추가, 수정 등 conf 파일 변경 후에는 `source startsv.sh`를 실행하면 supervisor 설치부터 각 프로젝트별 supervisor 작업 설정, 작업 실행까지 자동 적용된다.
## 현재 Soccer supervisor 설정(api, 스케쥴 - 작업 분리필요)
### 공통
### api 서버
- `logs monitor` // logs 파일 생성시 권한을 강제로 nginx로 변경
- `queue job` // 현재 redis 
- `websocket` // 
### 스케쥴 서버
- `short-schedule:run` // 

## supervisorctl 사용법
- `supervisorctl start all` // 모든 작업 실행
- `supervisorctl restart all` // 모든 작업 재실행
- `supervisorctl stop all` // 모든 작업 중단
- `supervisorctl status all` // 모든 작업 상태 보기
- `supervisorctl reload` // supervisord 재실행
- `supervisorctl help` // 도움말
- `supervior`에 새롭게 추가된 작업이 있을 경우 `supervisor reload` 후 모든 작업이 리로드 될 때까지 잠시 기다려야 한다. 그리고 `./startsv.sh` 실행하면 모두 재실행 됨.



# 웹소켓
## soketi 서버 사용
- [문서](https://docs.soketi.app/)
- pusher 프로토콜 지원(`pusher` 설정 그대로 사용 가능 = 유사시 `pusher`로 서비스 이전 가능)
- 설정 변수
```
SERVER_PORT=6001
MODE=full
SOKETI_DEBUG=1
SOKETI_DB_REDIS_HOST=redis #(호스트 주소)
SOKETI_DB_REDIS_PORT=6379
SOKETI_ADAPTER_DRIVER=redis
SOKETI_DB_REDIS_DB=9
SOKETI_DEFAULT_APP_ID=12345
SOKETI_DEFAULT_APP_KEY=ABCDEFG
SOKETI_DEFAULT_APP_SECRET=HIJKLMNOP

# SOKETI_DB_REDIS_USERNAME=xyz
# SOKETI_DB_REDIS_PASSWORD=xyz
# SOKETI_DB_REDIS_KEY_PREFIX=xyz
```
- 소켓 서버는 브로드 캐스팅을 위해선 `laravel project`와 1:1로 물려서 동작함. (`laravel`에서 소켓 `broadcast`를 컨트롤함, 반면에 client to server message는 `laravel`에서 기본적으로 컨트롤할 수 없음, 클라이언트 메세지는 `http`로 처리하는 것이 `Laravel`의 기본 방식)
- 단순히 클라이트 연결과 브로드 캐스팅을 받는 경우 `laravel project` 없이 소켓서버만 (`redis`)에 물려 사용 가능.

## 채널 정보
- prefix : `FS_` 는 env로 설정할 것!

### **ingame(live)**
#### formation
- event name: `.ingame_live.formation`
- channel name: `FS_ingame_live.formation.{schedule_id}`
#### user_lineup
- event name: `.ingame_live.user_lineup `
- channel name: `FS_ingame_live.user_lineup.{game_join_id} `
#### user_rank
- event name: `.ingame_live.user_rank`
- channel name: `FS_ingame_live.user_rank.{game_id}`
#### personal_rank
- event name: `.ingame_live.personal_rank`
- channel name: `FS_ingame_live.personal_rank.{game_id}.{user_id}`
#### player_core_stat
- event name: `.ingame_live.player_core_stat`
- channel name: `FS_ingame_live.player_core_stat.{game_id}.{player_id}`
#### lineup_detail
- event name: `.ingame_live.lineup_detail`
- channel name: `FS_ingame_live.lineup_detail.{player_id}`
#### commentary
- event name: `.ingame_live.commentary`
- channel name: `FS_ingame_live.commentary.{schedule_id}`
#### timeline
- event name: `.ingame_live.timeline`
- channel name: `FS_ingame_live.timeline.{schedule_id}`
#### schedule
- event name: `.public.schedule`
- channel name : `FS_public.schedule`

#### gameinfo
- event name: `.public.gameinfo`
- channel name : `FS_public.gameinfo`

#### simulation(sequence)
- event_name: .simulation.sequence
- channel name: FS_simulation.sequence.{schedule_id}  

## 웹소켓 채널 상태 통계 (DEV)
- https://dev.soccer.b2ggames.net/laravel-websockets (화면에서 Port 443으로 설정 후 **Connect**)
## supervisor 작업 로그 (DEV)
- http://dev.pc.soccer.b2ggames.net:9001

# pusher 설정
[기본세팅](https://beyondco.de/docs/laravel-websockets/basic-usage/pusher)
## production
### pusher host 설정
- `config/broadcasting.php` `connections` -> `pusher` -> `options` -> `host` , `port` 설정 필요


# Queue
## 종류
- high,default,low,email
- high - 빠르게 동작해야할 작업(라이브, 마켓)
- default - 기본
- low - (오래걸리는 계산 등등)
- email - email 발송
## queue를 늘리고 싶을 때 
- `supervisor_soccer.conf`에 추가한 후 `source startsv.sh` 실행 후 supervisor 재실행

# 이슈처리 참고
## price 변동 로직이 적용되지 않음 
- `ref_power_ranking_quantiles`에 해당 리그 관련 데이터가 제대로 업데이트되어있는지 확인.
## 유저플레이트 카드 **lock_status** 처리문제
-  `__wrapupUserPlateCardLock`
- ingame에서 game에 속한 경기가 **Played** 이외의 상태로 종료된 경우 admin에서 수동처리 시점에서 해당 경기에 참가한 `user_plate_card` 모두에 대해 `__wrapupUserPlateCardLock` 함수 실행 필요

# 미해결 이슈
- `cURL error 6: Could not resolve host:` : 간헐적 발생

# My page - ETC
- [마이페이지 MY Plate card _ ETC 상세 기준]
1. 현 소속 기준으로 필터링 
  단, 서비스 하지 않는 리그 또는 팀일 경우
   서비스한 가장 최근 리그, 팀으로 반영 

- [마이페이지 리그 필터]
1. 서비스하는 리그 모두 활성화