$(document).ready(function() { 
        $("#rankingdata").tablesorter( {
            widgets: ['zebra'],
            //headers: { 6: {sorter: false} },
            sortList: [ [0, 0] ]
            });
         $("#pairingdata").tablesorter( {
            widgets: ['zebra'],
            headers: {  0: {sorter: "text"},
                        1: {sorter: "digit"},
                        2: {sorter: "digit"},
                        3: {sorter: "digit"},
                        4: {sorter: "digit"},
                        //5: {sorter: "digit"},
                        6: {sorter: false} },
                        7: {sorter: "digit"},
                        8: {sorter: "digit"},
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

