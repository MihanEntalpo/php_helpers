Profiler
========

Profiler is a fully statical class, used for simple code profiling - measuring time and memory consumption of parts of code.

How to use it:

```php
//Initilizing, removing all old data
Profiler::init(); 

//function "measure" means starting of named measured part of code
Profiler::measure("Loading data"); 

//Any useful procedures...

$data = file_get_contents("c:/swapfile.sys");

//Every time you call a measure function with some "name" in parameter, profiler will end previous measured block of code
//and open next one.
Profiler::measure("Analyzing data"); 

//other useful code of yours
$summ=0;
for($i=0;$i<strlen($data); $i++)
{
  $summ += ord(substr($data,$i,1));
}

$avg = $summ / strlen($data);

echo "I've found average byte value of your swap file:" . $avg;

//To end current measured code block and don't start next one - you have to run measure() without parameters.
Profiler::measure();

//End now, output results:
Profiler::printMeasures();
```

Will output beautiful table of data, like this:

| Task name      | Runned | Time   |  Memory |
|----------------|--------|--------|---------|
| Loading data   |    1   |  51 sec|  8000 MB|
|Analyzing data  |    1   |2872 sec|    30 MB|


Also:
-----

Where are some useful functions:

```php
Profiler::formatMem($bytes) // outbut memory in human readable format.
Profiler::getCounters() // return all measured data in a array
Profiler::printMeasures(true) // output to console, not for web (replaces <br> to \n, doesn't use <table> and colors)
```
