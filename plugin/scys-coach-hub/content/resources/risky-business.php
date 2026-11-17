<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"risky-business","title":"Risky Business","ages":["U10","U12","U14"],"themes":["decisions","scoring","teamwork"],"minutes":"6 attacking lives each (source); duration unspecified","players":"2 teams; choose 1v1, 2v1 or 3v3 each life (source)","equipment":"6 balls; proposed goals, cones and pinnies.","setup":"Attackers start at side; defenders split behind goals.","instructions":["Attackers choose a format for each of six balls.","Source points: 1v1 goal 5; 2v1 goal 2; 3v3 goal 3.","Dead ball ends a life. Scorers stay and attack opposite goal.","Defenders connect a pass for one point; passing to coach ends game. Swap after six lives."],"support":["Proposed option: Offer two clear format choices first."],"challenge":["Proposed option: Change point values to invite a different decision (source allows this)."],"observe":["Who has a voice in choosing?","Do players consider challenge as well as reward?"],"sourceRefs":[{"sourceId":"guide-8","page":39}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
