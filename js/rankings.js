$(document).ready(function() {
        /*$.tablesorter.addWidget( {
            // from http://stackoverflow.com/questions/437290/exclude-a-column-from-being-sorted-using-jquery-tablesorter/437408#437408
            id: "indexFirstColumn",
            format: function(table) {   // format is called when the on init and when a sorting has finished
                        for(var i=0; i < table.tBodies[0].rows.length; i++) {
                            $("tbody tr:eq(" + (i - 1) + ") td:first",table).html(i);
                        }
                    }
            });*/

        $("#rankingdata").tablesorter( {
            widgets: ['zebra', 'indexFirstColumn'],
            headers: {  0: {sorter: "digit"},
                        1: {sorter: "text"},
                        2: {sorter: "digit"},
                        3: {sorter: "digit"},
                        4: {sorter: "digit"},
                        5: {sorter: "digit"},
                        6: {sorter: "digit"},
                        7: {sorter: "digit"},
                        8: {sorter: "digit"},
                        9: {sorter: "text"} },
            sortList: [ [0, 0] ]
            });
         $("#pairingdata").tablesorter( {
            widgets: ['zebra'],
            headers: {  0: {sorter: "text"},
                        1: {sorter: "digit"},
                        2: {sorter: "digit"},
                        3: {sorter: "digit"},
                        4: {sorter: "digit"},
                        5: {sorter: "text"},
                        6: {sorter: false},
                        7: {sorter: "digit"},
                        8: {sorter: "digit"} },
            sortList: [ [0, 0] ]
            });
        $("#comparedata").tablesorter( {
            widgets: ['zebra'],
            headers: {  0: {sorter: "text"},
                        1: {sorter: "digit"},
                        2: {sorter: "digit"},
                        3: {sorter: "digit"},
                        4: {sorter: "digit"},
                        5: {sorter: "digit"},
                        6: {sorter: "digit"},
                        7: {sorter: false},
                        8: {sorter: false},
                        9: {sorter: false} },
            sortList: [ [0, 0] ]
            });
        $("#battledata").tablesorter( {
            widgets: ['zebra'],
            headers: {  0: {sorter: "digit"},
                        1: {sorter: "digit"},
                        2: {sorter: "digit"},
                        3: {sorter: "digit"},
                        4: {sorter: "digit"},
                        5: {sorter: "digit"},
                        6: {sorter: "digit"},
                        7: {sorter: "digit"},
                        //8: {sorter: "digit"},
                        9: {sorter: "text"},
                        10: {sorter: false},
                        11: {sorter: false} },
            sortList: [ [4, 1] ]
            });
    });

