<?php

ini_set('memory_limit', '1024M');

class circuit
{
    public $id;
    public $H;
    public $E;
    public $P;
    public $jugglers;
    
    public function __construct($id, $H, $E, $P)
    {
        $this->id = $id;
        $this->H = $H;
        $this->E = $E;
        $this->P = $P;
        $this->jugglers = array();
    }
}

class juggler
{
    public $id;
    public $H;
    public $E;
    public $P;
    public $preferences;
    public $circuit;
    
    public function __construct($id, $H, $E, $P, $preferences, $circuits)
    {
        $this->id = $id;
        $this->H = $H;
        $this->E = $E;
        $this->P = $P;
        $this->preferences = explode(',', $preferences);
        $this->circuit = null;
    }
}

/**
 * Calculate the score for a juggler/circuit combination
 * 
 * @param juggler $juggler
 * @param circuit $circuit
 * @return int
 */
function calculateScore($juggler, $circuit)
{
    return ($juggler->H * $circuit->H) + ($juggler->E * $circuit->E) + ($juggler->P * $circuit->P);
}

/**
 * Iterate through all jugglers, assigning them to circuits recursively
 * 1. By preference
 * 2. If their preferred circuit is full
 *   a. If they are a better fit for the circuit than someone in it, bump that person
 *   b. Otherwise, check the next circuit in their list
 * 
 * @param juggler $juggler
 * @param array $circuits
 * @param array $jugglers
 */
function assignJuggler(&$juggler, &$circuits, &$jugglers)
{
    $jugglers_per_circuit = count($jugglers) / count($circuits);

    foreach ($juggler->preferences as $preference)
    {
        // Check for an opening in their preferred circuit
        if (count($circuits[$preference]->jugglers) < $jugglers_per_circuit)
        {
            $circuits[$preference]->jugglers[] = $juggler->id;
            $juggler->circuit = $preference;
            break;
        }
        
        // See if they are a better fit for the circuit than someone else in it
        else
        {
            foreach ($circuits[$preference]->jugglers as $key => $circuit_juggler_id)
            {
                // They are a better fit, bump that person
                if (calculateScore($juggler, $circuits[$preference]) > calculateScore($jugglers[$circuit_juggler_id], $circuits[$preference]))
                {
                    $circuits[$preference]->jugglers[$key] = $juggler->id;
                    $juggler->circuit = $preference;
                    
                    assignJuggler($jugglers[$circuit_juggler_id], $circuits, $jugglers);
                    
                    // Done comparing to jugglers in this circuit
                    break;
                    
                    // Done looking for a circuit for this juggler
                    break;
                }
            }
            
            // If they've gotten to here, they're not a better fit, try their next preference
        }
    }
}

if (empty($argv[1]) || !file_exists($argv[1]))
{
    die("Please specify the filename to be read in\n");
}
$filename = $argv[1];

if (!($handle = @fopen($filename, "r")))
{
    die("Unreadable filename '{$filename}'\n");
}

$circuits = $jugglers = array();

// Read in all of the data
while (!feof($handle))
{
    $buffer = fgets($handle);
    
    if ($buffer{0} == 'C')
    {
        //C C1990 H:6 E:5 P:1
        preg_match('/C\s([\w\d]+)\sH:(\d+)\sE:(\d+)\sP:(\d+)/', $buffer, $matches);
        $circuits[$matches[1]] = new circuit($matches[1], $matches[2], $matches[3], $matches[4]);
    }
    else if ($buffer{0} == 'J')
    {
        //J J1 H:2 E:9 P:1 C1067,C1364,C661,C1255,C1121,C260,C270,C1235,C664,C1484
        preg_match('/J\s([\w\d]+)\sH:(\d+)\sE:(\d+)\sP:(\d+)\s([\w\d,]+)/', $buffer, $matches);
        $jugglers[$matches[1]] = new juggler($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $circuits);
    }
}
fclose($handle);

// Assign all of the jugglers to circuits
foreach ($jugglers as $juggler)
{
    // Only assign jugglers that haven't been assigned yet
    if (empty($juggler->circuit))
    {
        assignJuggler($juggler, $circuits, $jugglers);
    }
}

// Output the results
foreach ($circuits as $circuit)
{
    // AroundTheWorld Bob AroundTheWorld:131 Factory:146, Ann AroundTheWorld:130 Factory:126
    $results = array();
    foreach ($circuit->jugglers as $juggler_id)
    {
        $result = '';
        $result = "{$juggler_id}";
        foreach ($jugglers[$juggler_id]->preferences as $preference)
        {
            $result .= " {$preference}:" . calculateScore($jugglers[$juggler_id], $circuits[$preference]);
        }
        $results[] = $result;
    }
    echo "{$circuit->id} " . implode(', ', $results) . "\n";
}

