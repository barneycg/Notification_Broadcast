<?php
ini_set("auto_detect_line_endings", true);
class Thread
{
    var $pref; // process reference
    var $pipes; // stdio
    var $buffer; // output buffer
   
    public function __constructor()
    {
        $this->pref = 0;
        $this->buffer = "";
        $this->pipes = (array )null;
    }

    public function Create($file)
    {
        $t = new Thread;
        $descriptor = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array
            ("pipe", "w"));
        $t->pref = proc_open("php -q $file ", $descriptor, $t->pipes);
        stream_set_blocking($t->pipes[1], 0);
        return $t;
    }

    public function isActive()
    {
        $this->buffer .= $this->listen();
        $f = stream_get_meta_data($this->pipes[1]);
        return !$f["eof"];
    }

    public function close()
    {
        $r = proc_close($this->pref);
        $this->pref = null;
        return $r;
    }

    public function tell($thought)
    {
        fwrite($this->pipes[0], $thought);
    }

    public function listen()
    {
        $buffer = $this->buffer;
        $this->buffer = "";
        //while ($r = fgets($this->pipes[1], 1024))
        while ($r = stream_get_line($this->pipes[1], 100,"\n\n"))
        {
            $buffer .= $r;
        }
        return $buffer;
    }

    public function getError()
    {
        $buffer = "";
        while ($r = fgets($this->pipes[2], 1024))
        {
            $buffer .= $r;
        }
        return $buffer;
    }
}

?>
