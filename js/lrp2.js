function formatDate(d) {
	return (d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate() + " " + d.toLocaleTimeString());
}

//var bot = {};
$(function () {
	var query = location.search;
	
	var game;
	if (query.indexOf("?") == 0) {
		query1 = query.substr(1);
		parameters = unescape(query1).split("&");
	}
	for (var i in parameters) {
		if (parameters[i].match("game=")) game = parameters[i].substring(parameters[i].indexOf("=") + 1);
		if (parameters[i].match("name=")) name = parameters[i].substring(parameters[i].indexOf("=") + 1);
	}
	if (!game || game == "") {
		$("#info").html("Invalid game parameter");
		return;
	}
	
	
	
	$("#info").html("Loading data, please wait...");
	$.ajax({
	    url: '/rumble/RatingDetailsJson' + query,
	    type: 'GET',
	    dataType: 'json',
	    timeout: 10000,
	    error: function(XMLHttpRequest, textStatus, errorThrown){
			$("#info").html("Error loading JSON document:<br />" + textStatus);
	        //alert('Error loading JSON document');
	    },
	    success: function(data){
	    	$("#info").html("Computing...");
	    	bot = data;//eval(text);
	    	if (bot.error) {
	    		$("#info").html("ERROR: " + bot.error);
	    		return;
	    	}
	    	lastBattle = new Date(bot.lastBattle);
	    	$("#info").html("");
	    	data_rank = [];
	    	//bot.data_pos = [];
	    	data_expected = [];
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

	    	for (var i = 0; i < bot.pairings.length; i++) {
	    		var p = bot.pairings[i];
	    		data_rank.push([p.ranking, p.PBI, p.name, p.expectedScore, p.numBattles, p.score]);
	    		if (p.ranking < minX) minX = p.ranking;
	    		if (p.ranking > maxX) maxX = p.ranking;
	    		Sx += p.ranking;
	    		Sy += p.PBI;
	    		Sxy += p.ranking * p.PBI;
	    		Sxx += p.ranking * p.ranking;
	    		//data_pos.push([i, p.PBI]);
	    		data_expected.push([p.expectedScore, p.PBI, p.name, p.ranking, p.numBattles, p.score]);
	    		if (p.expectedScore < minX2) minX2 = p.expectedScore;
	    		if (p.expectedScore > maxX2) maxX2 = p.expectedScore;
	    		Sx2 += p.expectedScore;
	    		Sy2 += p.PBI;
	    		Sxy2 += p.expectedScore * p.PBI;
	    		Sxx2 += p.expectedScore * p.expectedScore;
	    	}
	    	var Beta = (Sxy*bot.pairings.length-Sx*Sy)/(Sxx*bot.pairings.length-Sx*Sx);
	    	var Alpha = (Sy - Beta*Sx)/bot.pairings.length;
	    	var Beta2 = (Sxy2*bot.pairings.length-Sx2*Sy2)/(Sxx2*bot.pairings.length-Sx2*Sx2);
	    	var Alpha2 = (Sy2 - Beta2*Sx2)/bot.pairings.length;

	    	if (minX < 500) minX = 500; 
	    	lrp_rank = [[minX, Alpha + Beta * minX], [maxX, Alpha + Beta * maxX]];
	    	lrp_expected = [[minX2, Alpha2 + Beta2 * minX2], [maxX2, Alpha2 + Beta2 * maxX2]];
	    	
	    	//var plot1={};
	    	
	    	bot.plot = function(type) {
	    		bot.currType = type;
	    		if (type == 0) {
	    			this.graph = $.plot($("#graph"), 
	    					[{
	    						 data: data_rank,
	    						 points: { show: true }
	    					 },
	    					 {
	    						 data: lrp_rank,
	    						 label: "LRP",
	    						 lines: { show: true}
	    					 }],
	    					 {
	    						yaxis : {min: -30, max: 30},
	    						xaxis : {min: minX, max: maxX},
	    						grid: { hoverable: true, clickable: false }
	    					 }
	    			);
	    			$("#legend").html("X Axis: <b>Enemy ranking</b> (<a href='#' onclick='bot.plot(1);return false;'>expected</a>)<br />").
	    			append("Y Axis: <b>PBI</b>");
	    		}
	    		else {
	    			this.graph = $.plot($("#graph"),
	    					[
	    					 {
	    						 data: data_expected,
	    						 points: { show: true }
	    					 },
	    					 {
	    						 data: lrp_expected,
	    						 label: "LRP",
	    						 lines: { show: true }
	    					 }
	    					 ],
	    					 {
	    				yaxis : {min: -30, max: 30},
	    				xaxis : {min: minX2, max: maxX2},
	    				grid: { hoverable: true, clickable: false }
	    					 }
	    			);
	    			$("#legend").html("X Axis: <b>Expected score %</b> (<a href='#' onclick='bot.plot(0);return false;'>ranking</a>)<br />").
	    			append("Y Axis: <b>PBI</b>");
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
    	                    if (item.datapoint.length == 6)
    	                    	showTooltip(item.pageX, item.pageY,
	    	                                "<b>"+item.datapoint[2]+"</b><br />" +
	    	                                "Score: <b>"+item.datapoint[5].toFixed(2)+"</b> ("+item.datapoint[4]+" battle"+(item.datapoint[4] == 1 ? "" : "s")+")<br />"+
	    	                                (bot.currType == 0 ? "Ranking: " : "Expected: ") + "<b>" + item.datapoint[0].toFixed(2) + "</b><br />" + 
	    	                                "PBI: <b>" + item.datapoint[1] + "</b>");
	    	            }
	    	          }
	    	          else {
	    	             $("#tooltip").remove();
	    	             previousPoint = null;            
	    	          }
	    	    });

	    	    //$("#graph").bind("plotclick", function (event, pos, item) {
	    	    //    if (item) {
	    	    //        $("#clickdata").text("You clicked point " + item.dataIndex + " in " + item.series.label + ".");
	    	    //        plot.highlight(item.series, item.datapoint);
	    	    //    }
	    	    //});

	    	};
	    	bot.plot(0);
	    }
	});
	$.getJSON('/rumble/GetOlderVersionsJson' + query,
		function(data){
			if (data.versions && data.versions.length > 1) {
				versions = [];
				for (var i = 0; i < data.versions.length; i++) 
					if (data.versions[i] != name) versions.push(data.versions[i]);
				versions.invert;
				$("#debug").append("<br /><br />Compare with previous versions:");
				list = "<ul>";
				$.each(versions, function(i,item){
					if (item != name) {
						list += "<li><a href='compare.html?game="+game+"&name1="+name+"&name2="+item + "'>"+item+"</a></li>";
					}
				});
				$("#debug").append(list+"</ul>");
			}
	});
});