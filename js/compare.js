function formatDate(d) {
	return (d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate() + " " + d.toLocaleTimeString());
}

var bot1 = {};
var bot2 = {};

$(function () {
	var query = unescape(location.search);
	var game;
	if (query.indexOf("?") == 0) {
		query = query.substr(1);
		parameters = query.split("&");
	}
	for (var i in parameters) {
		if (parameters[i].match("game=")) game = parameters[i].substring(parameters[i].indexOf("=") + 1);
		if (parameters[i].match("name1=")) name1 = parameters[i].substring(parameters[i].indexOf("=") + 1);
		if (parameters[i].match("name2=")) name2 = parameters[i].substring(parameters[i].indexOf("=") + 1);
	}
	if (!game || game == "") {
		$("#info").html("Invalid game parameter");
		return;
	}
	$("#info").append(game + "<br />" + name1 + "<br />" + name2);
	$("#info").html("Loading data, please wait...<br />");
	$.ajax({
	    url: '/rumble/RatingDetailsJson?game=' + game + '&name=' + name1,
	    type: 'GET',
	    dataType: 'json',
	    timeout: 10000,
	    error: function(XMLHttpRequest, textStatus, errorThrown){
			$("#info").append("Error loading bot1 data: " + textStatus + "<br />");
	        //alert('Error loading JSON document');
	    },
	    success: function(data) {
	    	bot1 = data;
	    	if (bot1.error) {
	    		$("#info").append("bot 1 ERROR: " + bot1.error + "<br />");
	    	}
	    	else {
	    		$("#info").append("bot1 OK<br />");
	    		bot1.loaded = true;
	    		if (bot2.loaded) processData();
	    	}
	    }
	});
	
	$.ajax({
	    url: '/rumble/RatingDetailsJson?game=' + game + '&name=' + name2,
	    type: 'GET',
	    dataType: 'json',
	    timeout: 5000,
	    error: function(XMLHttpRequest, textStatus, errorThrown){
			$("#info").append("Error loading bot2 data: " + textStatus+ "<br />");
	        //alert('Error loading JSON document');
	    },
	    success: function(data){
	    	bot2 = data;
	    	if (bot2.error) {
	    		$("#info").append("bot 2 ERROR: " + bot2.error + "<br />");
	    	}
	    	else {
	    		$("#info").append("bot2 OK<br />");
	    		bot2.loaded = true;
	    		if (bot1.loaded) processData();
	    	}
	    }
	});
	
	function processData() {
		//<img src='flags/" + bot1.name.split(".")[0] + ".gif' style='float: left; margin-right: 10px'>
		$("#info").html("<table style='width: 600px'><tr><td><b>" + bot1.name + "</b><br /><br />" +
				"Current ranking: <b>" + bot1.rating+"</b><br />" +
		    	"Avg. Score: <b>" + (bot1.APS * 100).toFixed(2) + "</b><br />" +
		    	"Battles: <b>" + bot1.numBattles+"</b><br />" +
		    	"Specialization index: <b>" + bot1.specializationIndex.toFixed(2)+"</b><br />" +
		    	"Momentum: <b>" + bot1.momentum.toFixed(2) + "</b></td>" +				
				
				"<td align='center'>compared<br />to</td><td>"+"<b>" + bot2.name + "</b><br /><br />"+
				"Current ranking: <b>" + bot2.rating+"</b><br />" +
		    	"Avg. Score: <b>" + (bot2.APS * 100).toFixed(2) + "</b><br />" +
		    	"Battles: <b>" + bot2.numBattles+"</b><br />" +
		    	"Specialization index: <b>" + bot2.specializationIndex.toFixed(2)+"</b><br />" +
		    	"Momentum: <b>" + bot2.momentum.toFixed(2) + "</b>" +
				"</td></tr></table><p></p>");
		
		bot1.data_rank = [];
    	bot1.data_expected = [];
    	var Sx=0;
    	var Sy=0;
    	var Sxy=0;
    	var Sxx=0;
    	var minX = 1000;
    	var maxX = 0;
    	var Sx2=0;
    	var Sy2=0;
    	var Sxy2=0;
    	var Sxx2=0;
    	var minX2 = 1000;
    	var maxX2 = 0;
    	
    	$("body").append("<div id='comparisonT' style='display: none'></div>")
    	$("#comparisonT").append("<table id='comparisonTable'></table>");
    	$table = $("#comparisonTable");
    	var commonPairings = 0;
    	for (var i = 0; i < bot1.pairings.length; i++) {
    		var p1 = bot1.pairings[i];
    		for (var j in bot2.pairings) {
    			var p2 = bot2.pairings[j];
    			if (p2.name == p1.name) {
    				$table.append("<tr><td>"+p1.name+"</td><td>"+p1.score+"</td><td>"+p2.score+"</td></tr>");
    				commonPairings++;
    				//diff = (p1.score - p2.score) - (p1.expectedScore - p2.expectedScore);
    				diff = (p1.score - p2.score);
    				bot1.data_rank.push([p1.ranking, diff, p1.name]);
    				if (p1.ranking < minX) minX = p1.ranking;
    				if (p1.ranking > maxX) maxX = p1.ranking;
    				Sx += p1.ranking;
    				Sy += diff;
    				Sxy += p1.ranking * diff;
    				Sxx += p1.ranking * p1.ranking;
    				//data_pos.push([i, p.PBI]);
    				bot1.data_expected.push([p1.expectedScore, diff, p1.name]);
    				if (p1.expectedScore < minX2) minX2 = p1.expectedScore;
    				if (p1.expectedScore > maxX2) maxX2 = p1.expectedScore;
    				Sx2 += p1.expectedScore;
    				Sy2 += diff;
    				Sxy2 += p1.expectedScore * diff;
    				Sxx2 += p1.expectedScore * p1.expectedScore;
    			}
    		}
    	}
    	var Beta = (Sxy*commonPairings-Sx*Sy)/(Sxx*commonPairings-Sx*Sx);
    	var Alpha = (Sy - Beta*Sx)/commonPairings;
    	var Beta2 = (Sxy2*commonPairings-Sx2*Sy2)/(Sxx2*commonPairings-Sx2*Sx2);
    	var Alpha2 = (Sy2 - Beta2*Sx2)/commonPairings;

    	if (minX < 500) minX = 500; 
    	bot1.lrp_rank = [[minX, Alpha + Beta * minX], [maxX, Alpha + Beta * maxX]];
    	bot1.lrpAvg = bot1.lrp_rank[0][1] + (bot1.lrp_rank[1][1] - bot1.lrp_rank[0][1]) / 2;
    	bot1.lrp_expected = [[minX2, Alpha2 + Beta2 * minX2], [maxX2, Alpha2 + Beta2 * maxX2]];
    	bot1.plot = function(type) {
    		bot1.currType = type;
    		if (type == 0) {
    			this.graph = $.plot($("#graph"), 
    					[{data: bot1.data_rank, points: {show: true}},
    					 {data: bot1.lrp_rank, label: "LRP", lines: {show: true}}
    					],
    					 {
    						yaxis : {min: bot1.lrpAvg - 30, max: bot1.lrpAvg + 30},
    						xaxis : {min: minX, max: maxX},
    						grid: { hoverable: true, clickable: false }
    					 }
    			);
    			$("#legend").html("X Axis: <b>Enemy ranking</b> (<a href='#' onclick='bot1.plot(1);return false;'>expected</a>)<br />" +
    				//"Y Axis: <b>scoreDifference - expectedScoreDifference</b>");
    				"Y Axis: <b>scoreDifference</b>");
    		}
    		else if (type == 1) {
    			this.graph = $.plot($("#graph"),
    					[{data: bot1.data_expected, points: {show: true}},
    					 {data: bot1.lrp_expected, label: "LRP", lines: {show: true}}
    					],
    					 {
    						yaxis : {min: bot1.lrpAvg - 30, max: bot1.lrpAvg + 30},
    						xaxis : {min: minX2, max: maxX2},
    						grid: { hoverable: true, clickable: false }
    					 }
    			);
    			$("#legend").html("X Axis: <b>Expected score %</b> (<a href='#' onclick='bot1.plot(0);return false;'>ranking</a>)<br />" +
    				//"Y Axis: <b>scoreDifference - expectedScoreDifference</b>");
    				"Y Axis: <b>scoreDifference</b>");
    		}
    		
    		function showTooltip(x, y, contents) {
    	        $('<div id="tooltip">' + contents + '</div>').css( {
    	            position: 'absolute',
    	            display: 'none',
    	            top: y + 5,
    	            left: x + 5,
    	            border: '1px solid #fdd',
    	            padding: '2px',
    	            'background-color': '#fee',
    	            opacity: 0.80
    	        }).appendTo("body").fadeIn(200);
    	    }

    		
    		var previousPoint = null;
    	    $("#graph").bind("plothover", function (event, pos, item) {
    	        //$("#x").text(pos.x.toFixed(2));
    	        //$("#y").text(pos.y.toFixed(2));

	            if (item) {
	                if (previousPoint != item.datapoint) {
	                    previousPoint = item.datapoint;

	                    $("#tooltip").remove();
	                    //var x = item.datapoint[0].toFixed(2),
	                    //    y = item.datapoint[1].toFixed(2);
	                    if (item.datapoint.length == 3)
	                    	showTooltip(item.pageX, item.pageY,
	                    			"<b>"+item.datapoint[2]+"</b><br />" +
	                    			"Value: <b>"+item.datapoint[1].toFixed(2)+"</b>");// ("+item.datapoint[4]+" battle"+(item.datapoint[4] == 1 ? "" : "s")+")<br />"+
    	                                //(bot.currType == 0 ? "Ranking: " : "Expected: ") + "<b>" + item.datapoint[0].toFixed(2) + "</b><br />" + 
    	                                //"PBI: <b>" + item.datapoint[1] + "</b>");
    	            }
    	          }
    	          else {
    	             $("#tooltip").remove();
    	             previousPoint = null;            
    	          }
    	    });
    	};
    	bot1.plot(0);
	}
});