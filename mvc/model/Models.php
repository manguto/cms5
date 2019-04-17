<?php
namespace manguto\manguto\mvc\model;

use manguto\manguto\lib\Diretorios;
use manguto\manguto\lib\Arquivos;
use manguto\manguto\lib\Strings;


class Models
{
    const model_dir_array = [
        'sis/model'        
    ];
    
    const funcoes_padrao = ['__construct','preLoad','posLoad'];
    
    static function get(){        
        $files = self::getFiles();
        //deb($files);
        
        sort($files);
        //deb($files);
        
        $models = self::getModels($files);
        //deb($models);


        self::loadFunctions($models,$files);
        //debc($models);
        
        self::loadParameters($models);
        //debc($models);

        self::extendsRepository($models);
        //debc($models);
                
        return $models;
    }
    
    static private function getModels($files){
        $models=[];
        foreach ($files as $file){            
            $model_name = Arquivos::obterNomeArquivo($file,false);
            $models[$model_name] = [];
        }
        return $models;
    }
    
    static private function loadParameters(array &$models){
                
        foreach ($models as $model_name=>$info){
            //deb($model_name,0); debc($info,0);
            
            //inicializacao do parametro
            $models[$model_name]['parametros'] = [];
            
            foreach ($info['funcao_padrao'] as $funcao_nome=>$funcao_info){
                //deb($funcao_nome,0); debc($funcao_conteudo);
                //$funcao_argumentos = $funcao_info['argumentos'];
                $funcao_conteudo = $funcao_info['conteudo'];                
                
                if($funcao_nome=='preLoad'){
                    //debc($funcao_conteudo,0);
                    $funcao_preload_conteudo_ = explode(chr(10), $funcao_conteudo);
                    //debc($funcao_preload_conteudo_);
                    foreach ($funcao_preload_conteudo_ as $funcao_preload_conteudo_linha){
                        if(strpos($funcao_preload_conteudo_linha, '$this->values[')){
                            $pline = trim($funcao_preload_conteudo_linha);                            
                            $pline = str_replace("\$this->values['", '', $pline);
                            $pline = str_replace("']", '', $pline);
                            $pline = str_replace("'", "", $pline);
                            //deb($pline);
                            {
                                $pline_ = explode('=', $pline);
                                $pname = trim(array_shift($pline_));
                                //deb($pname);
                                {
                                    $pline_ = explode(';', array_pop($pline_));
                                    $pdef = trim(array_shift($pline_));
                                    //deb($pdef);
                                    $pcoment = trim(array_pop($pline_));
                                    $pcoment = str_replace('//', '', $pcoment);
                                    //deb($pcoment);
                                }
                                {
                                    $pref = substr($pname,-3)=='_id' ? substr($pname, 0,strlen($pname)-3) : '';
                                }
                            }
                            $models[$model_name]['parametros'][$pname]['padrao'] = $pdef;
                            $models[$model_name]['parametros'][$pname]['comentario'] = $pcoment;
                            $models[$model_name]['parametros'][$pname]['reference'] = $pref;
                            
                        }
                    }                    
                }
            }
        }
    }
    
    static private function getFiles(){
        $modelFiles = [];
        foreach (self::model_dir_array as $model_dir){
            $files = Diretorios::obterArquivosPastas($model_dir, false, true, false,['php']);
            foreach ($files as $file){
                if(strpos($file, 'Zzz')) continue;
                $modelFiles[] = $file;
            }
        }
        return $modelFiles;
    }
     
    static private function extendsRepository(array &$models){
        //deb($models); 
        foreach ($models as &$model){
            //deb($model);
            $conteudo = $model['conteudo'];
            //debc($conteudo);
            if(strpos($conteudo, 'extends Repository')!==false){
                $model['extends_repository'] = true;
            }else{
                $model['extends_repository'] = false;
            }
        }
        
    }
    
    static private function loadFunctions(array &$models, array $filename_array){
        $funcoes = [];
        foreach ($filename_array as $filename){
            
            $conteudo = Arquivos::obterConteudo($filename);
            //debc($conteudo);
            
            $model_name = Arquivos::obterNomeArquivo($filename,false);            
            //deb($model_name,0);
            
            {//parameters
                
                //separa o conteudo pelas funcoes
                $funcao_conteudo_ = explode(' function ', $conteudo);
                
                $models[$model_name]['funcao_padrao'] = [];
                $models[$model_name]['funcao_adicional'] = [];
                $models[$model_name]['conteudo'] = $conteudo;
                
                if(sizeof($funcao_conteudo_)>1){
                    //remove conteudo desnecessario
                    array_shift($funcao_conteudo_);
                    //debc($funcoes);
                    
                    foreach ($funcao_conteudo_ as $f){
                        //debc($f);
                        //nome da funcao
                        $f_ = explode('(', $f);
                        //debc($f_);
                        $funcao_nome = array_shift($f_);
                        //deb($funcao_nome,0);
                        //deb($f_);
                        {
                            $funcao_conteudo = implode('(', $f_);
                            //debc($funcao_conteudo);
                            {//obter argumentos da funcao
                                $funcao_conteudo_ = explode(')', $funcao_conteudo);
                                //remove parametros
                                $funcao_argumentos = array_shift($funcao_conteudo_);
                                $funcao_argumentos = str_replace(',', ', ', $funcao_argumentos);                                
                                $funcao_argumentos = str_replace(' = ', '=', $funcao_argumentos);                                
                                $funcao_argumentos = Strings::RemoverEspacamentosRepetidos($funcao_argumentos);
                                
                                //deb($funcao_parametros);
                                $funcao_conteudo = implode(')', $funcao_conteudo_);
                                //debc($funcao_conteudo);
                            }
                            {//remove inicio outra funcao
                                $funcao_conteudo_ = explode('}', $funcao_conteudo);
                                //deb($funcao_conteudo_);
                                //remove atributos da funcao seguinte
                                array_pop($funcao_conteudo_);
                                $funcao_conteudo = implode('}', $funcao_conteudo_).'}';
                                //debc($funcao_conteudo);
                            }
                            //debc($funcao_conteudo);
                        }
                        {
                            $tipo =  in_array($funcao_nome, self::funcoes_padrao) ? 'funcao_padrao' : 'funcao_adicional';
                        }
                        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
                        $models[$model_name][$tipo][$funcao_nome]['argumentos']=$funcao_argumentos;
                        $models[$model_name][$tipo][$funcao_nome]['conteudo']=$funcao_conteudo;
                        //<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
                    } 
                }               
            }
        }
    }
    
    
    static function get_repository_extended_modelnames(){
        $model_array = Models::get();
        //deb($model_array);
        foreach ($model_array as $modelname=>$model_information){
            //deb($model);
            $er = $model_information['extends_repository'];
            //deb($er);
            if($er){
                $modelname_show = explode('_',$modelname);
                $modelname_show = array_map('ucfirst',$modelname_show);
                $modelname_show = implode(' ', $modelname_show);
                
                $model_array[strtolower($modelname)] = $modelname_show;
            }
            unset($model_array[$modelname]);
        }
        //deb($model_array);
        return $model_array;
    }
    
}

?>