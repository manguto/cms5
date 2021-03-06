<?php
namespace manguto\cms5\lib;

use manguto\cms5\mvc\model\User;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class Logs
{

    const dir = 'log';

    const name = SIS_ABREV . '_LOG';

    const global_varname = 'logger';

    const formato_datahora = 'Y-m-d H_i_s';

    const formato_data_arquivo = 'Ymd_H';

    const formato_data_arquivo_diario = 'Ymd_H'; //para visualizacao no modulo de LOG (dev)

    // Detailed debug information
    public const TYPE_DEBUG = 'debug';

    // Interesting events Examples: User logs in, SQL logs.
    public const TYPE_INFO = 'info';
 
    // Uncommon events
    public const TYPE_NOTICE = 'notice';

    // Exceptional occurrences that are not errors Examples: Use of deprecated APIs, poor use of an API,undesirable things that are not necessarily wrong.
    public const TYPE_WARNING = 'warning';

    // Runtime errors
    public const TYPE_ERROR = 'error';

    // Critical conditions Example: Application component unavailable, unexpected exception.
    public const TYPE_CRITICAL = 'critical';

    // Action must be taken immediately Example: Entire website down, database unavailable, etc.This should trigger the SMS alerts and wake you up.
    public const TYPE_ALERT = 'alert';

    // Urgent alert.
    public const TYPE_EMERGENCY = 'emergency';

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    static function Start($extra_msg='')
    {
        {
            $extra_msg = $extra_msg!='' ? " - $extra_msg" : '';
        }
        self::resetLogInstance();
        self::set(Logs::TYPE_INFO, '######################################################################## '.date('H:i:s - d/m/Y').$extra_msg);
    }

    // ----------------------------------------------------------------------------------------------------------------------------------------------------------------
    static public function set($type = 'info', $msg = 'Ponto de Verificação!',array $parameters = [])
    {
        { // get log instance
            $logger = self::getLogInstance();
        }

        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        { // set!
            {
                $type = trim($type)=='' ? 'info' : $type;
            }
            $logger->$type("$msg", $parameters);
        }
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

        { // save log instance
            self::setLogInstance($logger);
        }
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * renova o arquivo de log com base nos novos parametros produzidos
     */
    static private function resetLogInstance(){
        if (isset($GLOBALS[self::global_varname])) {
            unset($GLOBALS[self::global_varname]);
        }        
    }
    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    static private function getLogInstance(): Logger
    {
        if (! isset($GLOBALS[self::global_varname])) {

            // Create the logger
            $logger = new Logger(self::name);

            // Now add some handlers
            $logger->pushHandler(new StreamHandler(self::getFilename(), Logger::DEBUG));
            $logger->pushHandler(new FirePHPHandler());

            // Save on globals
            self::setLogInstance($logger);
        }
        return $GLOBALS[self::global_varname];
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    static private function setLogInstance(Logger $logger)
    {
        $GLOBALS[self::global_varname] = $logger;
    }

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    static function getFilename()
    {
        {
            $date =  date(self::formato_data_arquivo);
        }
        {
            $sid = session_id();
            
        }        
        {
            $user = User::getSessionUser();
            if ($user !== false) {
                $userid = Numbers::str_pad_left($user->getId(), 3);
            } else {
                $userid = '000';
            }
        }
        
        //$filename = self::dir . DIRECTORY_SEPARATOR . $date . "_".$sid."_".$userid.".log";
        
        {//montagem do nome do arquivo com base na estrutura padrao
            $filename = self::filenameStruct();
            $filename = str_replace('DATE', $date, $filename);
            $filename = str_replace('SID', $sid, $filename);
            $filename = str_replace('USERID', $userid, $filename);
        }        
        
        return $filename;
    }
    
    /***
     * estrutura dos dados contidos no nome do arquivo de log
     * @return string
     */
    private static function filenameStruct($complete=true){
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        $return = "DATE_SID_USERID";
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        if($complete){
            $return = self::dir . DIRECTORY_SEPARATOR . $return . '.log';
        }
        return $return;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    static function getLastLogFileHTML()
    {
        
        {//obtem arquivos de log
            $logs = Diretorios::obterArquivosPastas(self::dir, false, true, false,['log']);
        }
        
        //deb($logs);
        {//remove logs que nao pertencam a session atual
            {//obtem o codigo da session atual
                {
                    $sid = session_id();
                    //deb($sid);
                    
                    $u = User::getSessionUser();                    
                    $uid = $u===false ? '000' : Numbers::str_pad_left($u->getId(),3);
                    //deb($uid);
                }
                $term = $sid."_".$uid;
                //deb($term,0);
            }
            foreach ($logs as $k=>$log) {
                if(strpos($log, $term)===false){
                    unset($logs[$k]);
                }
            }
            //deb($logs,0);
        }
        
        if(sizeof($logs)>0){
            {
                $filename = array_pop($logs);
                $content = Arquivos::obterConteudo($filename);
                {//substituicoes para evitar interpretacao de JSON do navegador, entre outras
                    $content = str_replace('[]', '', $content);
                    $content = str_replace('[', '', $content);
                    $content = str_replace(']', ' |', $content);
                }
            }
            
            
            $return = "<pre>".chr(10);
            $return .= $content;
            $return .= chr(10)."</pre>";
        }else{
            $return = "Nenhum arquivo de log encontrado para a sessão ($sid) e usuário atuais ($uid). [$term]";
        }
        
        return $return;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    
    
    static function getDayLogs($day) {
        $dayLogs = [];
        
        $logs = Diretorios::obterArquivosPastas(Logs::dir, false, true, false,['log']);
        //deb($logs,0);
        
        foreach ($logs as $key=>$filepath) {
            //deb($log);
            
            //evita arquivos que nao tenham o dia (data) informada
            if(strpos($filepath, $day)!==false){
                
                $dayLogs[$key] = self::getLogfileInfo($filepath);
                
            }
        }
        
        return $dayLogs;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    
    
    static function getYearLogs($year) {
        //deb($year);
        $dayLogs = [];
        
        $logs = Diretorios::obterArquivosPastas(Logs::dir, false, true, false);
        //deb($logs,0);
        
        foreach ($logs as $key=>$filepath) {
            //deb($log);
            
            $filename = Arquivos::obterNomeArquivo($filepath);
            //deb($filename);
            
            $log_year = substr($filename, 0,4);
            //deb($log_year);
            
            //evita arquivos que nao tenham o dia (data) informada
            if(strval($year)==strval($log_year)){                
               
                $dayLogs[$key]=self::getLogfileInfo($filepath);                               
                                
            }
        }
        
        return $dayLogs;
    }
    
    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    static private function getLogfileInfo($filepath) {
        $return=[];
        
        {//filename
            $return['FILENAME'] = $filepath;
        }
        
        {//outras informacoes padrao
            $logInfo = str_replace(self::dir.DIRECTORY_SEPARATOR, '', $filepath);
            $logInfo = str_replace('.log', '', $logInfo);            
            $logInfo = explode('_', $logInfo);
            //deb($logInfo);
            
            $fileStruct = explode('_',self::filenameStruct(false));
            //deb($fileStruct);
            
            foreach ($fileStruct as $index=>$infoName){
                $return[$infoName] = $logInfo[$index];
            }
        }
        {//informacoes extras
            $DATE = $return['DATE'];
            $date_o = new Datas($DATE,self::formato_data_arquivo);
            
            $return['YEAR'] = $date_o->getDate('Y');
            $return['MONTH'] = $date_o->getDate('m');
            $return['DAY'] = $date_o->getDate('d');
        }
        return $return;
    }
    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
    /**
     * registra um log temporario para testes/visualizacao
     * @param string $msg
     */
    static function temp(string $msg='') {
        $filename = 'log/temp_'.date('Ymd').'.txt';
        $msg = date('H:i:s d-m-Y').' | '.$msg.chr(10);        
        Arquivos::escreverConteudo($filename, $msg, FILE_APPEND);
    }
    // ---------------------------------------------------------------------------------------------------------------------------------------------------------------
}

?>