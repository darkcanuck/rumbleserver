$(document).ready(function() { 
        $("#rankingdata").tablesorter( {
            widgets: ['zebra'],
            //headers: { 6: {sorter: false} },
            sortList: [ [0, 0] ]
            });
         $("#pairingdata").tablesorter( {
            widgets: ['zebra'],
            headers: { 6: {sorter: false} },
            sortList: [ [0, 0] ]
        });
        $("#battledata").tablesorter( {
            widgets: ['zebra'],
            headers: { 10: {sorter: false}, 11: {sorter: false} },
            sortList: [ [4, 1] ]
        });
    });

