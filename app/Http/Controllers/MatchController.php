<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Matches;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    // matches :--------------------------------------------->
    // Return all matches
    public function index()
    {
        $matches = DB::table('matches')->get();
        return response()->json($matches);
    }

    public function bySeason($season)
    {
        $matches = DB::table('matches as m')
        ->join('teams as ht', 'm.team_a_id', '=', 'ht.team_id')
        ->join('teams as at', 'm.team_b_id', '=', 'at.team_id')
        ->leftJoin('referees as r', 'm.referee_id', '=', 'r.referee_id')
        ->select(
           'm.*',                   // all columns from matches
           'ht.team_name as team_a',
           'at.team_name as team_b',
           'r.name as referee_name'
        )
        ->where('m.season', $season)   // filter by season
        ->get();

        return response()->json($matches);
    }


    public function createMatch(Request $request)
    {
        $id = DB::table('matches')->insertGetId([
            'match_id' => $request->input('match_id'),
            'round_id' => $request->input('round_id'),
            'comp_season_id' => $request->input('comp_season_id'),
            'group_stage_id' => $request->input('group_stage_id'),
            'round_name' => $request->input('round_name'),
            'round_code' => $request->input('round_code'),
            'season' => $request->input('season'),
            'kickoff_datetime' => $request->input('kickoff_datetime'),
            'match_status' => $request->input('match_status'),
            'venue_id' => $request->input('venue_id'),
            'referee_id' => $request->input('referee_id'),
            'game_code' => $request->input('game_code'),
            'grade' => $request->input('grade'),
             'ruleset_version' => $request->input('ruleset'),
             'total_point' => $request->input('total_point', 0), // default 0
              'ball_in_play' => $request->input('ball_in_play', 0),


            'half_length_sec' => $request->input('half_length_sec' , 0),
            'et_half_length_sec' => $request->input('et_half_length_sec' , 0),
            'extra_time_possible' => $request->input('extra_time_possible'),
            'penalty_shootout_possible' => $request->input('penalty_shootout_possible'),
            'neutral_flag' => $request->input('neutral_flag', 'N'),
            'team_a_id' => $request->input('team_a_id'),
            'team_b_id' => $request->input('team_b_id'),
            'video_source' => $request->input('video_source'),
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => "system",
            'updated_by' => "system",
        ],);


    }


    // create match context 

    public function creatematch_context(Request $request){
        try {
            $id = DB::table('match_contexts')->insertGetId([
                'context_id' => $request->input('context_id'),
                'match_id' => $request->input('match_id'),
                'season' => $request->input('season'),
                'competition_season_id' => $request->input('competition_season_id'),
                'round_id' => $request->input('round_id'),
                'match_date_time' => $request->input('match_date_time'),
                'venue_id' => $request->input('venue_id'),
                'referee_id' => $request->input('referee_id'),
                'code' => $request->input('code'),
                'ruleset_version' => $request->input('ruleset_version'),
                'periods_json' => $request->input('periods_json'),
                'environment_json' => $request->input('environment_json'),
                'ends_json' => $request->input('ends_json'),
                'teamA_json' => $request->input('teamA_json'),
                'teamB_json' => $request->input('teamB_json'),
                'context_version' => $request->input('context_version'),
                'status' => $request->input('status'),
                'locked' => $request->input('locked'),
                'locked_at' => now(),
                'locked_by' => $request->input('locked_by'),
                'created_at' => now(),
                'created_by' => $request->input('created_by'),
                'updated_at' => now(),
                'updated_by' => $request->input('updated_by'),
            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Saved successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'not saved',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }


    // players :--------------------------------------------->

    public function getPlayers(){
        $players = DB::table('players as p')
        ->leftJoin('teams as t', 't.team_id', '=', 'p.team_id')
        ->select(
            'p.*',
            't.team_name as team_name',
            't.code as code',
        )
        ->orderBy('p.sr_no', 'asc')
        ->get();

        return response()->json($players);
    }

    public function getPlayersByTeam($teamId){
        $players = DB::table('players')
            ->where('team_id', $teamId)
            ->get();
            
        return response()->json($players);
    }

    // creating a player
    public function createPlayer(Request $request){
        try {
            $id = DB::table('players')->insertGetId([
                'player_id' => $request->input('id'),
                'team_id' => $request->input('team'),
                'display_name' => $request->input('name'),
                'preferred_position' => $request->input('position'),
                'dominant_side' => $request->input('dominant_side'),
                'active_flag' => 'active',
            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Player created successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Player not created',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }


    // get player analytics 

    public function getPlayerAnalytics(){
    $player = DB::table('players as p')
    ->leftJoin('teams as t', 't.team_id', '=', 'p.team_id')
    ->leftJoin('lineups as l', 'l.player_id', '=', 'p.player_id')
    ->leftJoin('matches as m', 'm.match_id', '=', 'l.match_id')
    ->leftJoin('events as e', function($join) {
        $join->on('e.match_id', '=', 'm.match_id')
             ->where('e.shooter_player_id', '=', DB::raw('p.player_id'));
    })
    ->select(
        'p.player_id',
        'p.display_name',
        't.team_name',
        // Count all games played (starter or sub)
        DB::raw('COUNT(DISTINCT l.match_id) as games_played'),
        // Count games played as starter
        DB::raw('COUNT(DISTINCT CASE WHEN l.starter_flag = 1 THEN l.match_id END) as starter_games'),
        // Sum minutes played including extra time, converted to minutes
        DB::raw('ROUND(SUM(
            CASE 
                WHEN l.starter_flag = 1 THEN 
                    (CAST(m.half_length_sec AS UNSIGNED) * 2) 
                    + (CASE WHEN m.extra_time_possible = 1 THEN CAST(m.et_half_length_sec AS UNSIGNED) * 2 ELSE 0 END)
                ELSE 15 * 60
            END
        ) / 60, 1) as total_minutes'),
        // Count goals
        DB::raw('COUNT(DISTINCT CASE WHEN e.shot_result = "goal" THEN e.event_id END) as goals'),
        // Count points
        DB::raw('COUNT(DISTINCT CASE WHEN e.shot_result = "point" THEN e.event_id END) as points'),
        // Count all shots
        DB::raw('COUNT(DISTINCT CASE WHEN e.event_type = "shot" THEN e.event_id END) as fp_att')
    )
    ->groupBy('p.player_id', 'p.display_name', 't.team_name')
    ->get();


        return response()->json($player);
    }


    

    // competition :--------------------------------------------->

     public function getCoreCompetition(){
        $competitions = DB::table('competitions')
        ->orderBy('sr_no', 'asc')
        ->get();

        return response()->json($competitions);
    }



    public function getCompetition(){
        $competitions = DB::table('competitions')
        ->join('competition_seasons as cs', 'competitions.id', '=', 'cs.competition_id')
        ->select(
            'competitions.*',
            'cs.id as comp_season_id',
            'cs.season as season',
        )
        ->orderBy('sr_no', 'asc')
        ->get();

        return response()->json($competitions);
    }

    public function getCompetitionBySeason($season){
        $competitions = DB::table('competition_seasons as cs')
        ->join('competitions as c', 'cs.competition_id', '=', 'c.id')
        ->select(
            'c.game_code',
            'c.name as competition_name',
            'c.code as competition_code',
            'cs.*',
        )
        ->where('cs.season', $season)
        ->orderBy('sr_no', 'asc')
        ->get();

        return response()->json($competitions);
    }

    // create a new competition
    public function createCompetition(Request $request){
        try {
            $id = DB::table('competitions')->insertGetId([
                'id'     => $request->input('id'),
                'name'   => $request->input('name'),
                'code'        => $request->input('code'),
                'game_code' => $request->input('game_code'),
                'grade' =>  $request->input('grade'),
                'level' => $request->input('level'),
                'county'  => $request->input('county'),
                'province'  =>  $request->input('province'),
                'flags'  =>  $request->input('flags'),
                'notes'  =>  $request->input('notes'),
                'created_at' => now(),
                'updated_at' => now(),

            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Competition created successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Competition not created',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }




    // venue :--------------------------------------------->

    public function getVenue(){
        $venues = DB::table('venues')
         ->orderBy('sr_no', 'asc')
        ->get();

        return response()->json($venues);
    }

    // create a new venue 
    public function createVenue(Request $request){
        try {
            $id = DB::table('venues')->insertGetId([
                'venue_id'     => $request->input('id'),
                'venue_name'   => $request->input('name'),
                'location'        => $request->input('location'),
                'capacity' => $request->input('capacity'),
                'surface_type' =>$request->input('type'),
                'home_team_id' => $request->input('home_team'),
                'created_at' => now(),
                'updated_at' => now(),

            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Venue created successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Venue not created',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }


      // get venue analytics 

    public function getVenueAnalytics(){
        $venue = DB::table('venues as v')
        ->leftJoin('matches as m', 'm.venue_id', '=', 'v.venue_id')
        ->select(
            'v.venue_name',
            'v.location',
            'v.capacity',
            'v.surface_type',
            DB::raw('COUNT(DISTINCT m.match_id) as games_played')
        )
        ->groupBy('v.venue_name', 'v.location', 'v.capacity', 'v.surface_type')
        ->orderBy('v.sr_no', 'asc')
        ->get();



        return response()->json($venue);
    }




    // team :--------------------------------------------->
    // fetch all teams 
    public function getTeams(){
        $teams = DB::table('teams as t')
        ->leftJoin('players as p', 't.team_id', '=', 'p.team_id')
        ->select(
            't.team_id',
            't.team_name',
            't.code',
            't.active_flag',
            DB::raw('COUNT(p.player_id) as player_count')
        )
        ->groupBy('t.sr_no','t.team_id', 't.team_name', 't.code', 't.active_flag')
        ->orderBy('t.sr_no', 'asc')
        ->get();

        return response()->json($teams);
    }

    // creating a team 
    public function createTeam(Request $request){
        try {
            $id = DB::table('teams')->insertGetId([
                'team_id'     => $request->input('id'),
                'team_name'   => $request->input('name'),
                'code'        => $request->input('code'),
                'active_flag' => 'active',
            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Team not created',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }

    // get team analytics 

     public function getTeamsAnalytics(){
         $teams = DB::table('possessions as p')
    ->leftJoin('teams as t', 't.team_id', '=', 'p.team_id')
    ->leftJoin('matches as m', 'm.match_id', '=', 'p.match_id')
    ->leftJoin('events as e', function($join) {
        $join->on('e.match_id', '=', 'p.match_id')
             ->where('e.event_type', '=', 'restart')
             ->where('e.restart_type', '=', 'kickout');
    })
    ->select(
        'p.team_id',
        't.team_name',
        'm.half_length_sec',
        'm.et_half_length_sec',
        't.total_point',
        DB::raw('SUM(p.duration_sec) as total_possession_time_sec'),
        DB::raw('COUNT(DISTINCT p.sr_no) as possession_count'),
        DB::raw('COUNT(DISTINCT CASE WHEN e.length_band = "short" THEN e.event_id END) as short_kickout_count'),
        DB::raw('COUNT(DISTINCT e.event_id) as total_kickout_count'),
        DB::raw('(SELECT SUM(pa.points_for) FROM possessions as pa WHERE pa.match_id = p.match_id AND pa.team_id != p.team_id) as points_allowed'),
        DB::raw('(
            (SELECT SUM(pa.points_for) FROM possessions as pa WHERE pa.match_id = p.match_id AND pa.team_id != p.team_id) 
            / NULLIF((SELECT COUNT(*) FROM possessions as pa WHERE pa.match_id = p.match_id AND pa.team_id != p.team_id), 0) * 100
        ) as drtg'),
        // Only added this for opponent short kickouts
        DB::raw('(SELECT COUNT(*) FROM events as oe WHERE oe.match_id = p.match_id AND oe.taken_by_team_id != p.team_id AND oe.event_type = "restart" AND oe.restart_type = "kickout" AND oe.length_band = "short") as opp_short_kickout_count')
    )
    ->groupBy('p.team_id', 't.team_name', 'm.half_length_sec', 'm.et_half_length_sec', 't.total_point', 'p.match_id')
    ->orderBy('p.sr_no', 'asc')
    ->get();

        return response()->json($teams);
    }



    // referee :--------------------------------------------->

    public function getReferees(){
       $referees = DB::table('referees as r')
    ->leftJoin('matches as m', 'r.referee_id', '=', 'm.referee_id')
    ->select(
        'r.referee_id',
        'r.sr_no',
        'r.name',
        'r.association',
        'r.level',
        'r.experience_years',
        'r.active_flag',
        'r.created_at',
        'r.updated_at',
        DB::raw('COUNT(m.match_id) as match_count')
    )
    ->groupBy(
        'r.referee_id',
        'r.sr_no',
        'r.name',
        'r.association',
        'r.level',
        'r.experience_years',
        'r.active_flag',
        'r.created_at',
        'r.updated_at'
    )
    ->orderBy('r.sr_no', 'asc')
    ->get();


        return response()->json($referees);
    }


    // create a new referee
    public function createReferee(Request $request){
        try {
            $id = DB::table('referees')->insertGetId([
                'referee_id'     => $request->input('id'),
                'name'   => $request->input('name'),
                'association'        => $request->input('association'),
                'level' => $request->input('level'),
                'experience_years' =>$request->input('experience_years'),
                'active_flag' => 'active',
                'created_at' => now(),
                'updated_at' => now(),

            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Referee created successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Referee not created',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }





    // possession :--------------------------------------------->

     public function getPossession(){
          $possession = DB::table('possessions as p')
    ->leftJoin('matches as m', 'm.match_id', '=', 'p.match_id')
    ->leftJoin('venues as v', 'v.venue_id', '=', 'm.venue_id') // join venue table
    ->select(
        'm.match_id',
        'v.venue_id',
        'v.venue_name',  // fetch venue name from venue table
        'm.total_point',
        DB::raw('COUNT(p.possession_id) as total_possessions')
    )
    ->groupBy('m.match_id', 'v.venue_id', 'v.venue_name', 'm.total_point')
    ->orderBy('p.sr_no', 'asc')
    ->get();

        return response()->json($possession);
    }


     // ELO :--------------------------------------------->

     public function getELO(){
        $teamElo = DB::table('team_elo_snapshot')
                   ->leftJoin('teams as t', 'team_elo_snapshot.team_id', '=', 't.team_id')
                   ->select(
                       'team_elo_snapshot.*',
                       't.team_name'
                   )
                   ->orderBy('team_elo_snapshot.elo', 'asc')
                   ->get();

        return response()->json($teamElo);
    }






    // lineups :--------------------------------------------->

    public function createLineup(Request $request){
        
        // $id = DB::table('lineups')->insertGetId([
        //     'match_id' => $request->input('match_id'),
        //     'team_id' => $request->input('team_id'),
        //     'player_id' => $request->input('player_id'),
        //     'lineup_type' => $request->input('lineup_type'), // starter or bench
        //     'position' => $request->input('position'), // position on field
        //     'created_at' => now(),
        //     'updated_at' => now(),
        //     'created_by' => "system",
        //     'updated_by' => "system",
        // ],);
    }





     // possession :--------------------------------------------->

    public function createPossession(Request $request){
          try {
            $id = DB::table('possessions')->insertGetId([
                'possession_id'     => $request->input('id'),
                'match_id'   => $request->input('name'),
                'team_id'        => $request->input('code'),
                'period' => '',
                'start_time_sec' => '',
                'end_time_sec' => '',
                'duration_sec' => '',
                'start_event_id' => '',
                'start_cause' => '',
                'start_restart_type' => '',
                'end_event_id' => '',
                'end_cause' => '',
                'shot_event_id' => '',
                'points_for' => '',
                'sequence_id' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'possession saved successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'possession not saved',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }







    // event :--------------------------------------------->

    public function createEvent(Request $request){
          try {
            $id = DB::table('events')->insertGetId([
                'event_id'     => $request->input('id'),
                'match_id'   => $request->input('matchId'),
                'period'        => $request->input('period'),
                'timestamp_sec' => $request->input('ts'),
                'event_type' => $request->input('event_type'),
                'team_id' => $request->input('team_id') ?? null,
                'possession_id' => $request->input('possession_id') ?? null,
                'shooter_player_id' => $request->input('shooter_player_id') ?? null,
                'shot_type' => $request->input('shot_type') ?? null,
                'shot_result' => $request->input('shot_result') ?? null,
                'x' => $request->input('x') ?? null,
                'y' => $request->input('y') ?? null,
                'under_pressure' => $request->input('under_pressure') ?? null,
                'on_balance' =>  $request->input('on_balance') ?? null,
                'arc_status' =>  $request->input('arc_status') ?? null,
                'shot_value_pts'  =>  $request->input('shot_value_pts') ?? null,
                'assist_player_id' =>  $request->input('assist_player_id') ?? null,
                'second_assist_player_id'  =>  $request->input('second_assist_player_id') ?? null,
                'blocker_player_id' =>  $request->input('blocker_player_id') ?? null,
                'goalkeeper_player_id' =>  $request->input('goalkeeper_player_id') ?? null,
                'time_to_shot_sec'  =>  $request->input('time_to_shot_sec') ?? null,
                'linked_restart_event_id'  =>  $request->input('linked_restart_event_id') ?? null,
                'restart_type'  =>  $request->input('restart_type') ?? null,
                'taken_by_team_id'  =>  $request->input('taken_by_team_id') ?? null,
                'won_by_team_id'  =>  $request->input('won_by_team_id') ?? null,
                'outcome_restart'  =>  $request->input('outcome_restart') ?? null,
                'target_zone'  =>  $request->input('target_zone') ?? null,
                'first_receiver_player_id'  =>  $request->input('first_receiver_player_id') ?? null,

                'retained'  =>  $request->input('retained') ?? null,
                'restart_side'  =>  $request->input('side') ?? null,
                'restart_line'  =>  $request->input('line') ?? null,
                
                'length_band'  =>  $request->input('length_band') ?? null,
                'dest_line'  =>  $request->input('dest_line') ?? null,
                'dest_side'  =>  $request->input('dest_side') ?? null,
                'clean_winner_player_id'  =>  $request->input('clean_winner_player_id') ?? null,
                'break_winner_player_id'  =>  $request->input('break_winner_player_id') ?? null,
                'crossed_40m'  =>  $request->input('crossed_40m') ?? null,
                'mark_type'  =>  $request->input('mark_type') ?? null,
                'catch_x'  =>  $request->input('catch_x') ?? null,
                'catch_y'  =>  $request->input('catch_y') ?? null,
                'awarded_team_id' =>  $request->input('awarded_team_id') ?? null,
                'against_team_id'  =>  $request->input('against_team_id') ?? null,
                'free_type'  =>  $request->input('free_type') ?? null,
                'free_outcome'  =>  $request->input('free_outcome') ?? null,
                'advantage_played'  =>  $request->input('advantage_played') ?? null,
                'advanced_50m'  =>  $request->input('advanced_50m') ?? null,
                'foul_category'  =>  $request->input('foul_category') ?? null,
                'fouled_player_id'  =>  $request->input('fouled_player_id') ?? null,
                'fouling_player_id'  =>  $request->input('fouling_player_id') ?? null,
                'spot_x'  =>  $request->input('spot_x') ?? null,
                'spot_y'  =>  $request->input('spot_y') ?? null,
                'won_by_turnover_team_id'  =>  $request->input('won_by_turnover_team_id') ?? null,
                'lost_by_turnover_team_id'  =>  $request->input('lost_by_turnover_team_id') ?? null,
                'turnover_mechanism'  =>  $request->input('turnover_mechanism') ?? null,
                'turnover_forced'  =>  $request->input('turnover_forced') ?? null,
                'winner_player_id'  =>  $request->input('winner_player_id') ?? null,
                'loser_player_id'  =>  $request->input('loser_player_id') ?? null,
                'to_x'  =>  $request->input('to_x') ?? null,
                'to_y'  =>  $request->input('to_y') ?? null,
                'card_player_id'  =>  $request->input('card_player_id') ?? null,
                'card_type'  =>  $request->input('card_type') ?? null,
                'card_reason'  =>  $request->input('card_reason') ?? null,
                'sub_team_id'  =>  $request->input('sub_team_id') ?? null,
                'off_player_id'  =>  $request->input('off_player_id') ?? null,
                'on_player_id'  =>  $request->input('on_player_id') ?? null,
                'sub_reason'  =>  $request->input('sub_reason') ?? null,
                'time_on_sec'  =>  $request->input('time_on_sec') ?? null,
                'throw_in_won_by_team_id'  =>  $request->input('throw_in_won_by_team_id') ?? null,
                'sideline_to_team_id'  =>  $request->input('sideline_to_team_id') ?? null,
                'period_marker'  =>  $request->input('period_marker') ?? null,
                'note_text'  =>  $request->input('note_text') ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'updated_by' => 'user',
                'created_by' => 'user',

            ]);

            //  Insert worked
            return response()->json([
                'success' => true,
                'message' => 'Event saved successfully',
                'team_id' => $id
            ], 201);
    
        } catch (\Exception $e) {
            //  Insert failed
            return response()->json([
                'success' => false,
                'message' => 'Event not saved',
                'error'   => $e->getMessage()
            ], 500);
        }  
    }

    public function getEntities(Request $request)
    {
        $type = $request->query('type', 'teams'); // e.g., "teams", "players", "venues", "refs"
        $search = $request->query('search', ''); // optional search filter

        switch ($type) {
            case 'players':
                $query = DB::table('players')
                    ->select(
                        'player_id as id',
                        'display_name as name',
                        'team_id as subtitle'
                    );
                break;

            case 'venues':
                $query = DB::table('venues')
                    ->select(
                        'venue_id as id',
                        'venue_name as name',
                        'location as subtitle'
                    );
                break;

            case 'refs':
                $query = DB::table('referees')
                    ->select(
                        'referee_id as id',
                        'name',
                        'experience_years as subtitle'
                    );
                break;

            default: // teams
                $query = DB::table('teams')
                    ->select(
                        'team_id as id',
                        'team_name as name',
                        'total_point as subtitle'
                    );
                break;
        }

        // Optional search filter
        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $data = $query->limit(50)->get();

        return response()->json($data);
    }

    public function comparison(Request $request)
    {
        $type = $request->query('type', 'teams');
        $ids = explode(',', $request->query('ids', ''));

        if (empty($ids) || count($ids) < 1) {
            return response()->json(['error' => 'No entity IDs provided'], 400);
        }

        // 🟩 Handle team-based comparison
        if ($type === 'teams') {
            $data = $this->compareTeams($ids);
            return response()->json(['metrics' => $data]);
        }

        // 🟨 Extend later for players, venues, refs...
        return response()->json(['error' => 'Unsupported comparison type'], 400);
    }

    private function compareTeams(array $teamIds)
    {
        // ✅ Calculate metrics from the matches table
        $teamStats = DB::table('matches')
            ->select(
                DB::raw('team_a_id as team_id'),
                DB::raw('AVG(CAST(total_point AS DECIMAL(10,2))) as avg_points'),
                DB::raw('COUNT(match_id) as matches_played'),
                DB::raw('SUM(CAST(total_point AS DECIMAL(10,2))) as total_points')
            )
            ->whereIn('team_a_id', $teamIds)
            ->groupBy('team_a_id')
            ->get();

        // ✅ Format in the frontend’s expected format
        return [
            [
                'id' => 'avg_points',
                'label' => 'Average Total Points',
                'values' => $teamStats->pluck('avg_points', 'team_id'),
            ],
            [
                'id' => 'matches_played',
                'label' => 'Matches Played',
                'values' => $teamStats->pluck('matches_played', 'team_id'),
            ],
            [
                'id' => 'total_points',
                'label' => 'Total Points',
                'values' => $teamStats->pluck('total_points', 'team_id'),
            ],
        ];
    }



}
