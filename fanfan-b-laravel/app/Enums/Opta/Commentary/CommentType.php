<?php

namespace App\Enums\Opta\Commentary;

use BenSampo\Enum\Enum;

final class CommentType extends Enum
{
  // Manual:
  const ASSIST = "assist";
  const COMMENT = "comment";
  const COMMENT_AUTO = "comment_auto";
  const COMMENT_IMPORTANT = "comment_important";
  const COMMENT_MIXED = "comment_mixed";
  const FULL_TIME = "full time";
  const GOAL = "goal"; // common
  const HALF_TIME = "half time";
  const HALF_TIME_SUMMARY = "half_time summary";
  const HIGHLIGHT = "highlight";
  const KICK_OFF = "kick off";
  const OWN_GOAL = "own goal"; // common
  const PENALTY = "penalty";
  const PENALTY_GOAL = "penalty goal"; // common
  const PENALTY_MISS = "penalty miss"; // common
  const PENALTY_SO_GOAL = "penalty_so goal";
  const PENALTY_SO_MISS = "penalty_so miss"; // common
  const PENALTY_SAVE = "penalty save"; // common
  const PENALTY_SAVED = "penalty saved"; // common
  const POST_MATCH_SUMMARY = "post-match_summary";
  const PRE_KICK_OFF = "pre kick off";
  const RED_CARD = "red card"; // common
  const SECOND_HALF = "second half";
  const STATS = "stats";
  const SUBSTITUTION = "substitution"; // common
  const TEAM_NEWS = "team news";
  const VAR = "var";
  const YELLOW_CARD = "yellow card"; // common
  const SECOND_YELLOW_RED_CARD = "second_yellow red card"; // common
  const SECONDYELLOW_CARD = "secondyellow card";

  // Auto:
  const ATTEMPT_BLOCKED = "attempt blocked";
  const ATTEMPT_SAVED = "attempt saved";
  const CONTENTIOUS_REFEREE_DECISIONS = "contentious referee decisions";
  const CORNER = "corner";
  const end_1 = "end 1";
  const end_2 = "end 2";
  const end_3 = "end 3";
  const end_4 = "end 4";
  const end_5 = "end 5";
  const end_10 = "end 10";
  const end_11 = "end 11";
  const end_12 = "end 12";
  const end_13 = "end 13";
  const end_14 = "end 14";
  const end_16 = "end 16";
  const END_DELAY = "end delay";
  const FREE_KICK_LOST = "free kick lost";
  const FREE_KICK_WON = "free kick won";
  const LINEUP = "lineup";
  const MISS = "miss";
  const OFFSIDE = "offside";
  const PENALTY_LOST = "penalty lost";
  const PENALTY_WON = "penalty won";
  const PLAYER_RETIRED = "player retired";
  const POST = "post"; //common
  const POSTPONED = "postponed";
  const START = "start";
  const START_DELAY = "start delay";
  const VAR_CANCELLED_GOAL = "VAR cancelled goal";
  const DELETED_AFTER_REVIEW = "Deleted after review";
  // Common
}
