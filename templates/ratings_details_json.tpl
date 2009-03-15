{{* $Id$ *}}
{
"name": "{{$name}}",
"game": "{{$game}}",
"rating": {{$details.rating_classic|string_format:"%.1f"}},
"numBattles": {{$details.battles}},
"lastBattle" : {{$details.unixtimestamp}},
"specializationIndex": {{$details.special|string_format:"%.3f"}},
"momentum": {{$details.momentum|string_format:"%.3f"}},
"APS": {{$details.percent_score|string_format:"%.6f"}},
"pairings": [    
{{foreach from=$pairings key=id item=bot}}
    {   "name": "{{$bot.vs_name}}",
        "ranking": {{$bot.rating_classic|string_format:"%.1f"}},
    	"score": {{$bot.score_pct|string_format:"%.3f"}},
    	"numBattles": {{$bot.battles}},
    	"lastBattle": {{$bot.unixtimestamp}},
    	"expectedScore": {{$bot.expected|string_format:"%.3f"}},
    	"PBI": {{$bot.pbindex|string_format:"%.3f"}}
        },
{{/foreach}}
    ] 
}
