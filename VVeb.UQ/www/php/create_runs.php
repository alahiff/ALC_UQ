<?php

// --- Get date
$date_full = getdate();
$date = $date_full[mday]."-".$date_full[month]."-".$date_full[year];
$date = $date."---".$date_full[hours]."-".$date_full[minutes]."-".$date_full[seconds];

// --- Get Image name
$image_name = $_POST["docker_image_run"];
$container_name = $image_name;
$container_name = str_replace('/','_',$container_name);
$container_name = str_replace(':','_',$container_name);
$workdir_name   = 'workdir_'.$date.'_'.$container_name;
$container_name = 'VVebUQ_CONTAINER_'.$date.'_'.$container_name;

// --- Get the number of cpu available (THIS NEEDS TO BE GENERALISED PROPERLY!!!)
$n_cpu = (int)$_POST["n_cpu"];

// --- Get the file name
$filename = $_POST["input_file_name"];

// --- Get the format of the input file
$file_ext = $_POST["input_file_type"];
$file_type = 'netcdf';
if ($file_ext == 'csv') {$file_type = 'csv';}

// --- Get the file name
$data_filename = $_POST["input_data_file_name"];

// --- Get run-dir
$run_dir = shell_exec('cat config.in');
$run_dir = str_replace("\n", '', $run_dir);
$name_split = preg_split('/VVeb.UQ/', $run_dir);
$dakota_dir = $name_split[0].'user_interface/';

// --- Produce files in run directory of container
$work_dir        = '/VVebUQ_runs';
$base_dir        = $work_dir.'/'.$workdir_name;
$mount_dir       = $run_dir.$workdir_name;
$files_dir       = $base_dir.'/files_for_dakota';
$input_file      = $work_dir.'/'.$filename;
$data_input_file = $work_dir.'/'.$data_filename;
$args_file       = $files_dir.'/arguments_for_dakota_script.txt';
shell_exec('mkdir -p '.$base_dir);
shell_exec('mkdir -p '.$files_dir);
shell_exec('cp '.$input_file.' '.$files_dir.'/'.$filename);
shell_exec('cp '.$data_input_file.' '.$files_dir.'/'.$data_filename);
shell_exec('cp ../interfaces/run_script.perl '.$base_dir.'/');
shell_exec('chmod +x '.$base_dir.'/run_script.perl');
shell_exec('printf \''.$container_name.' '.$mount_dir.' '.$image_name.' '.$filename.' '.$file_type.' '.$data_filename.' '.$dakota_dir.'\' > '.$args_file);

// --- Produce Dakota input file based on netcdf file provided by user
$command = 'docker exec -w '.$base_dir.' -t dakota_container python3 /dakota_user_interface/python/main.py -d run_script.perl -c '.$n_cpu.' -i '.$input_file.' -o '.$base_dir.'/dakota_run.in -t '.$file_type;
shell_exec($command);

// --- Run Container
$command = 'docker exec -w '.$base_dir.' -t dakota_container dakota -i ./dakota_run.in -o dakota_run.out';
shell_exec('printf \''.$command.'\n\' &> /VVebUQ_runs/terminal_command.txt');
shell_exec($command.' &> /VVebUQ_runs/terminal_output.txt');

// --- Go Home! (Said Nigel Fromage)
header("Location: {$_SERVER['HTTP_REFERER']}");
exit;

?>
