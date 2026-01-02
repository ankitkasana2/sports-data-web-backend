<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Matches; 
use App\Models\Team;

class EventController extends Controller
{
    public function getEventsByMatch($match_id)
    {
        try {

            // Correct table name: matches (NOT match)
            $match = Matches::from('matches as m')
                ->where('m.match_id', $match_id)
                ->leftJoin('teams as ta', 'ta.team_id', '=', 'm.team_a_id')
                ->leftJoin('teams as tb', 'tb.team_id', '=', 'm.team_b_id')
                ->select(
                    'm.team_a_id',
                    'ta.team_name as team_a_name',
                    'm.team_b_id',
                    'tb.team_name as team_b_name'
                )
                ->first();

            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Match not found'
                ]);
            }

            // Events for this match
            $events = Event::where('match_id', $match_id)
                ->select('period', 'event_type', 'team_id', 'shot_result', 'possession_id', 'timestamp_sec')
                ->orderBy('timestamp_sec', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'team_a_id' => $match->team_a_id,
                'team_a_name' => $match->team_a_name,
                'team_b_id' => $match->team_b_id,
                'team_b_name' => $match->team_b_name,
                'events' => $events
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error fetching events',
                'error' => $e->getMessage()
            ]);

        }
    }
}
