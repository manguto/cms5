<?php
namespace manguto\cms5\lib\database\repository;

use manguto\cms5\lib\Arquivos;
use manguto\cms5\lib\Diretorios;
use manguto\cms5\lib\Strings;
use manguto\cms5\lib\Exception;
use manguto\cms5\lib\ServerHelp;
use manguto\cms5\lib\ProcessResult;
use manguto\cms5\mvc\model\User;
use manguto\cms5\lib\Datas;
use manguto\cms5\lib\Arrays;
use manguto\cms5\lib\model\Model;

class _OFF_Repository extends Model 
{

    // pasta onde serao disponibilizados os arquivos de dados
    private const foldername = 'repository';

    // pasta onde serao disponibilizados os eventuais arquivos de dados iniciais ou base
    private const foldernameini = 'repository/ini';

    // nomes reservados que nao podem ser utilizados como nomes de parametros
    private const private_parameter_names = [
        'data'
    ];

    // nome a ser utilizado como nome do repositorio do objeto em questao
    private $repositoryname;

    // ======================================================================================

    // chaves primarias de um repositorio
    protected $primary_keys = [];

    // parametros a serem utilizados com filtros de pesquisa
    public $key_filters = [];

    // definicao dos nomes para exibicao dos parametros
    public $key_names = [];

    // ======================================================================================

    // parametros de controle
    const CtrlParameters = [
        'creation_user_id',
        'creation_datetime',
        'modification_user_id',
        'modification_datetime'
    ];

    // sentenca a ser utilizada nos condicoes para ordenacao dos resultados
    const order_by_tag = 'ORDER BY';

    // ==================================================================================== PUBLIC
    // ==================================================================================== PUBLIC
    // ==================================================================================== PUBLIC
    public function __construct(int $id = 0)
    {
        $this->checkParametersNamesAllowed();
        $this->Load($id);
    }

    /**
     * checks if all parameters names are allowed
     *
     * @throws Exception
     */
    private function checkParametersNamesAllowed()
    {
        { // verificacao de nomes de parametros restritos
            $private_parameters_names = self::private_parameter_names;
            // deb($private_parameters_names);
            $object_parameters_names = array_keys($this->values);
            // deb($object_parameters_names);

            foreach ($private_parameters_names as $ppn) {
                if (in_array($ppn, $object_parameters_names)) {
                    // deb($ppn,0);
                    throw new Exception("Foi encontrado um parâmetros com nome restrito ao sistema ('$ppn'). Modifique-o e tente novamente.");
                }
            }
        }
    }

    private function Load(int $id)
    {
        $id = intval($id);
        // verifica se foram definidos os valores (parametros) do objeto
        if (sizeof($this->values) == 0) {
            throw new Exception("Nenhum parâmetro definido para o objeto '" . $this->getModelname() . "'. Insira algum parâmetro e tente novamente.");
        }

        // configura toda a estrutura de variaveis e parametros para utilizacao do objeto
        parent::__construct($id);

        // set repository name
        $this->repositoryname = strtolower($this->tablename);

        // parametros de controle gerencial
        $this->CtrlParametersInitialize();

        // caso tenha sido infomado algum id, carrega os atributos do objeto
        $this->gather();

        // parametros de controle gerencial
        $this->CtrlParametersSet();

        // ordena os parametros de maneira a faciliar o entendimento quando da visualizacao bruta
        $this->valuesOrderSet();
    }

    /**
     * funcao utilizada para execucao apos o carregamento do objeto
     */
    public function posLoad()
    {}

    /**
     * inicializa os parametros de controle, de forma
     * que estes possam ser acessados pelas funcoes
     * "set" e "get"
     */
    private function CtrlParametersInitialize()
    {
        $cps = self::CtrlParameters;
        foreach ($cps as $cp) {
            $key = _OFF_RepositoryReferences::ctrl_parameter_ini . $cp;
            $this->values[$key] = '';
        }
    }

    /**
     * obtem um array com todos os parametros de controle e seus valores
     *
     * @return array
     */
    private function CtrlParametersSet()
    {
        $ctrl_parameters = [];

        { // =========================================================================== general parameters
            $cpi = _OFF_RepositoryReferences::ctrl_parameter_ini;
            $user_id = User::getSessionUserDirectAttribute('id');
            $datetime = date(Datas::FormatoDatahora);
        }

        { // -------------------------------------------------------------------- creation_user_id
            $pn = $cpi . 'creation_user_id';
            if ($this->getId() == 0 || $this->values[$pn] == '') {
                $ctrl_parameters[$pn] = $user_id;
            }
        }
        { // -------------------------------------------------------------------- creation_datetime
            $pn = $cpi . 'creation_datetime';
            if ($this->getId() == 0 || $this->values[$pn] == '') {
                $ctrl_parameters[$pn] = $datetime;
            }
        }
        { // -------------------------------------------------------------------- modification_user_id
            $pn = $cpi . 'modification_user_id';
            $ctrl_parameters[$pn] = $user_id;
        }
        { // -------------------------------------------------------------------- modification_datetime
            $pn = $cpi . 'modification_datetime';
            $ctrl_parameters[$pn] = $datetime;
        }

        { // ========================================================================= set!
            foreach ($ctrl_parameters as $k => $v) {
                $this->values[$k] = $v;
            }
        }

        return $ctrl_parameters;
    }

    /**
     * ordena os valores para facilitar entendimento dos mesmos
     */
    private function valuesOrderSet()
    {
        { // definicao dos valores a serem ordenados
            $values_ord = [];
            { // id
                $values_ord['id'] = null;
            }
            { // ctrl parameters
                foreach (self::CtrlParameters as $ctrl_parameter_name) {
                    $key = _OFF_RepositoryReferences::ctrl_parameter_ini . $ctrl_parameter_name;
                    $values_ord[$key] = null;
                }
            }
            // deb($values_ord);
        }
        { // ordenacao propriamente dita
            foreach ($this->values as $k => $v) {
                $values_ord[$k] = $v;
            }
            $this->values = $values_ord;
        }
    }

    public function save()
    {
        if ($this->save_check_primary_keys() == false) {
            throw new Exception("Já existe um registro com parâmetros chave idênticos! Utilize-o!");
        }

        $repositoryArray = self::getRepositoryARRAY($this->getModelname());
        if ($this->getId() == 0) {
            $id = sizeof($repositoryArray) + 1;
            $this->setId($id);
            if (isset($repositoryArray[$this->getId()])) {
                throw new Exception("ATENÇÃO! Não foi possível realizar o salvamento do registro atual. A indexação do repositório '" . $this->getModelname() . "' está incorreta. Contate o Administrador.");
            }
        }
        $repositoryArray[$this->getId()] = $this->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = true, $referencesIncluded = false, $singleLevelArray = false);
        self::saveRepositoryARRAY($this->repositoryname, $repositoryArray);
    }

    public function save_check_primary_keys(): bool
    {
        return self::static_primary_keys_check($this->getModelname(), $this->values);
    }

    static function static_primary_keys_check(string $tablename, array $parameters): bool
    {
        { // obtencao lista chaves primarias
            $tablenameFull = self::getObjectClassname($tablename);
            $obj_sample = new $tablenameFull();
            $pk_array = $obj_sample->primary_keys;
            // deb($pk);
        }

        // verifica se foi informado algum parametro como chave primaria,
        // se não, já retorna TRUE (tudo ok!)
        if (sizeof($pk_array) == 0) {
            return true;
        }

        { // conditions
            $conditions = [];

            { // percorrimento dos parametros informados
                foreach ($pk_array as $pk) {

                    // verifica se o parametro necessario foi informado
                    if (! isset($parameters[$pk])) {
                        throw new Exception("Um parâmetro necessários à análise de chaves primárias do repositório '$tablename' não foi encontrado dentre os parametros informados ('$pk').");
                    }

                    $conditions[] = " \$$pk=='" . $parameters[$pk] . "' ";
                }
            }
            { // ajuste das condicoes com base em registros pre-existentes (edicao)
                if (! isset($parameters['id'])) {
                    throw new Exception("O identificador necessário para análise de chaves primárias do repositório '$tablename' não foi encontrado dentre os parametros informados ('$pk').");
                } else {
                    $id = intval($parameters['id']);
                    if ($id != 0) {
                        $conditions[] = " \$id!='$id' ";
                    }
                }
            }
            $conditions = implode(' && ', $conditions);
            // deb($conditions);
        }
        { // busca de registros já existentes
            $registro_pre_existente_array = self::getRepository($tablename, $conditions, true, false, false, false);
            // deb($registro_array);
            if (sizeof($registro_pre_existente_array) > 1) {
                throw new Exception("ATENÇÃO! Foram encontrados registros com chaves primárias iguais (" . implode(',', $pk_array) . "). Contate o administrador!");
            }
        }
        { // VERIFICACAO FINAL!!!
            if (sizeof($registro_pre_existente_array) > 0) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }

    private function gather()
    {
        // apenas carrega caso haja algo para carregar (id!=0)
        if ($this->getId() != 0) {
            // deb($this->repositoryname);
            $repositoryArray = self::getRepositoryARRAY($this->repositoryname);
            // deb($repositoryArray);
            $registerid = $this->getId();
            // deb($registerid);
            $repositoryName = $this->repositoryname;
            // deb($repositoryName,0);

            if (isset($repositoryArray[$registerid])) {
                $this->SET_DATA($repositoryArray[$this->getId()]);
            } else {
                $msg = "O registro de identificador '$registerid' não foi encontrado no repositório '$repositoryName'.";
                // deb($msg);
                throw new Exception($msg);
            }
        }
    }

    public function delete()
    {
        // deb($this);
        $repositoryArray = self::getRepositoryARRAY($this->repositoryname);
        // deb($repositoryArray);
        if (isset($repositoryArray[$this->getId()])) {
            // deb($this);
            $idOld = $this->getId();
            $idDeleted = abs($this->getId()) * (- 1);
            $this->setId($idDeleted);
            $repositoryArray[$idDeleted] = $this->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = true, $referencesIncluded = false, $singleLevelArray = false);
            unset($repositoryArray[$idOld]);
            self::saveRepositoryARRAY($this->repositoryname, $repositoryArray);
        } else {
            throw new Exception("O registro de identificador '" . $this->getId() . "', não foi encontrado no repositório '" . $this->repositoryname . "'.");
        }
    }

    // ==================================================================================== STATIC
    // ==================================================================================== STATIC
    // ==================================================================================== STATIC

    /**
     * obtem o conteudo do modelo em forma de array
     *
     * {@inheritdoc}
     */
    public function GET_DATA(bool $extraIncluded, bool $ctrlParametersIncluded, bool $referencesIncluded, bool $singleLevelArray): array
    {
        // deb($singleLevelArray,0);
        $data = parent::GET_DATA($extraIncluded, $ctrlParametersIncluded, $referencesIncluded, $singleLevelArray);
        return $data;
    }

    public function getRepositoryname()
    {
        return $this->repositoryname;
    }

    /**
     * obtem uma lista de registros do repositorio em questao
     *
     * @param string $conditions
     * @param bool $returnAsObject
     * @param bool $loadReferences
     * @param boolean $loadCtrlParameters
     * @return array
     */
    static function getList(string $conditions, bool $returnAsObject, bool $loadReferences): array
    {
        { // ajuste conditions
            $conditions = trim($conditions);
        }
        { // obtencao do nome do repositorio
            $called_class = get_called_class();
            $class_sample = new $called_class();
            $repositoryname = $class_sample->getRepositoryname();
            // deb($repositoryname);
        }
        return self::getRepository($repositoryname, $conditions, $returnAsObject, $loadReferences, $loadCtrlParameters = false);
    }

    /**
     * obtem uma lista de registros do repositorio em questao
     * com base nos argumentos enviados
     *
     * @param array $args
     * @param bool $returnAsObject
     * @return array
     */
    static function getListBy(array $args, bool $returnAsObject = true): array
    {
        // deb($args);
        { // certifica que os parametros enviados estejam como arrays (multipla filtragem)
            foreach ($args as $key => $arg) {
                if (! is_array($arg)) {
                    $args[$key] = [
                        $arg
                    ];
                }
            }
        }

        // deb($args);
        extract($args);
        unset($args);
        unset($arg);
        unset($key);
        $vars = get_defined_vars();
        // deb($vars);
        unset($vars['returnAsObject']);
        /* */

        {
            $conditions = [];
            foreach ($vars as $varName => $varArray) {
                // deb($varArray);
                if (substr($varName, - 4) == '_ids') {

                    { // PARAMETROS ESPECIFICOS
                        { // PRODUTO_IDS
                          // deb($produto_ids);
                            $conditions_sub = [];
                            foreach ($varArray as $var_id_tmp) {
                                // deb($produto_id_tmp,0);
                                if (trim($var_id_tmp) == '') {
                                    continue;
                                }
                                $conditions_sub[] = " in_array($var_id_tmp, explode(',', trim(\$$varName))) ";
                            }
                            if (sizeof($conditions_sub) > 0) {
                                $conditions[] = ' ( ' . implode(' || ', $conditions_sub) . ' ) ';
                            }
                        }
                    }
                } else {

                    { // PARAMETROS em GERAL
                        if (sizeof($varArray) > 0) {
                            $conditions_sub = [];
                            // deb($varArray,0);
                            foreach ($varArray as $varValue) {
                                // deb($varValue,0);
                                if (trim($varValue) != '') {
                                    $conditions_sub[] = " \$$varName==$varValue ";
                                }
                            }
                            if (sizeof($conditions_sub) > 0) {
                                if (sizeof($conditions_sub) > 1) {
                                    $conditions[] = ' ( ' . implode(' || ', $conditions_sub) . ' ) ';
                                } else {
                                    $conditions[] = implode(' || ', $conditions_sub);
                                }
                            }
                        }
                    }
                }
            }

            $conditions = implode(' && ', $conditions);
            // deb($conditions,0);
        }
        $return = self::getList($conditions, $returnAsObject, $loadReferences = true, $loadCtrlParameters = false);
        // deb($return);
        return $return;
    }

    /**
     * obtem a quantidade de registros para um determinado repositorio e condicoes
     *
     * @param string $repositoryname
     * @param string $condition
     * @return int
     */
    static function getRepositoryLength(string $repositoryname, string $condition = ''): int
    {
        $repository = self::getRepository($repositoryname, $condition);
        // deb($repository);
        $repositoryLength = sizeof($repository);
        return $repositoryLength;
    }

    /**
     * obtem um repositorio conforme as condicoes informadas
     *
     * @param string $repositoryname
     * @param string $conditions
     * @param bool $returnAsObject
     * @param bool $loadReferences
     * @param bool $loadCtrlParameters
     * @param bool $singleLevelArray
     * @return array
     */
    static function getRepository(string $repositoryname, string $conditions = '', bool $returnAsObject = true, bool $loadReferences = true, bool $loadCtrlParameters = false, bool $singleLevelArray = true): array
    {
        $FULL_REPOSITORY_ARRAY = self::getRepositoryARRAY($repositoryname);

        { // ------------------------------------------------------- CONDITION HANDLMENT (filters)
          // ORDER ANALISYS
            $orderby = self::orderByAnalisys($conditions);
            // deb($orderby);
            // FILTER ANALISYS
            $conditions = self::filterAnalisys($conditions);
            // deb($conditions,0);
        } // ------------------------------------------------------------------------------------

        // deb($repositoryARRAY);
        foreach ($FULL_REPOSITORY_ARRAY as $id => $entry_asArray) {

            extract($entry_asArray);
            $condition = "\$conditionsConfirmed = ( $conditions );";
            // deb($condition,0);
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            eval($condition);
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            if (! $conditionsConfirmed) {
                unset($FULL_REPOSITORY_ARRAY[$id]);
                continue;
            }
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

            { // ---------------------------------------------------------------- LOAD PARAMETERS
                $objectClassname = self::getObjectClassname($repositoryname);
                // deb($objectClassname,0);
                $entryModel = new $objectClassname();
                // deb($entryModel,0);
                $entryModel->SET_DATA($entry_asArray);
                // deb($entryModel);
                // -----------------------------------
                // pos load - loading
                $entryModel->posLoad();
            }

            { // ---------------------------------------------------------------- LOAD REFERENCES
              // load references (other objects)
                if ($loadReferences) {
                    $entryModel->loadReferences();
                }
            }

            { // ---------------------------------------------------------------- RETURN STRUCTURE
                if ($returnAsObject == true) {
                    $FULL_REPOSITORY_ARRAY[$id] = $entryModel;
                } else {
                    $FULL_REPOSITORY_ARRAY[$id] = $entryModel->GET_DATA($loadReferences, $loadCtrlParameters, $referencesIncluded = true, $singleLevelArray = false);

                    { // --------------------------------------- SINGLE LEVEL ARRAY
                        if ($singleLevelArray) {
                            foreach ($FULL_REPOSITORY_ARRAY as &$R) {
                                $R = Arrays::arrayMultiNivelParaSimples($R);
                            }
                        }
                    } // ----------------------------------------------------------
                }
            }
        }

        { // -------------------------------------------------------------------- ORDER BY ACTION

            if ($orderby !== false) {
                $FULL_REPOSITORY_ARRAY = self::SortBy($FULL_REPOSITORY_ARRAY, $orderby);
            }
        } // ------------------------------------------------------------------------------------

        // deb($FULL_REPOSITORY_ARRAY);
        return $FULL_REPOSITORY_ARRAY;
    }

    // ============================================================================================================================== INTERNAL AUX FUNCTIONS
    // ============================================================================================================================== INTERNAL AUX FUNCTIONS
    // ============================================================================================================================== INTERNAL AUX FUNCTIONS
    // ============================================================================================================================== INTERNAL AUX FUNCTIONS
    // ============================================================================================================================== INTERNAL AUX FUNCTIONS
    private static function filterAnalisys($conditions)
    {

        // troca de aspas simples por duplas
        $conditions = str_replace("'", '"', $conditions);

        if ($conditions == '') {
            $conditions = " \$id>0 ";
        } elseif ($conditions == 'FULL') {
            $conditions = " true ";
        } else {
            $conditions = " \$id>0 && ( $conditions )";
        }

        { // excecao quando da utilizacao de "=" ao inves de "=="
            $conditions = str_replace('==', '=', $conditions);
            $conditions = str_replace('=', '==', $conditions);
            $conditions = str_replace('<==', '<=', $conditions);
            $conditions = str_replace('>==', '>=', $conditions); /* */
        }

        // deb($conditions);
        return $conditions;
    }

    /**
     * verifica a existencia de algum criterio de ordenacao
     *
     * @param string $conditions
     * @return boolean|string
     */
    private static function orderByAnalisys(string &$conditions)
    {

        // deb($conditions,0);
        if (strpos($conditions, self::order_by_tag) !== FALSE) {
            $conditions_info = explode(self::order_by_tag, $conditions);
            // deb($conditions_info);
            {
                $conditions = trim($conditions_info[0]);
                $orderby = trim($conditions_info[1]);
                // deb($conditions,0); deb($orderby);
            }
        } else {
            $orderby = false;
        }

        return $orderby;
    }

    /**
     * obter o conteudo da tabela em csv
     *
     * @param string $repositoryname
     * @return string
     */
    private static function getRepositoryCSV(string $repositoryname): string
    {
        // nome do arquivo em questao 'tabela.csv'
        $repositoryFilename = self::getRepositoryFilename($repositoryname);
        // deb($filename);
        // verificacao se o arquivo ja existe
        self::saveRepositoryCSV_START($repositoryname, $repositoryFilename);

        // obtencao do conteudo
        $repositoryCSV = Arquivos::obterConteudo($repositoryFilename);

        // transformar codificacao do texto
        $repositoryCSV = utf8_encode($repositoryCSV);
        // deb($repositoryCSV);
        return $repositoryCSV;
    }

    // ==============================================================================================================================-

    /**
     * salvar o conteudo da tabela em arquivo (csv)
     *
     * @param string $repositoryname
     * @param string $repositoryCSV
     */
    private static function saveRepositoryCSV(string $repositoryname, string $repositoryCSV)
    {
        // verificacao do diretorio
        Diretorios::mkdir(Repository::foldername);
        // transformar codificacao do texto
        $repositoryCSV = utf8_decode($repositoryCSV);
        // salvar arquivo
        Arquivos::escreverConteudo(Repository::getRepositoryFilename($repositoryname), $repositoryCSV);
    }

    // ==============================================================================================================================-

    /**
     * inicializa o conteudo da tabela em arquivo (csv) caso esta ainda nao exista
     *
     * @param string $repositoryname
     * @param string $filename
     */
    private static function saveRepositoryCSV_START(string $repositoryname, string $repositoryFilename)
    {
        // $arquivoExiste = file_exists($repositoryFilename);
        $arquivoExiste = self::repositoryFileExist($repositoryname);
        // deb($arquivoExiste);

        if (! $arquivoExiste) {

            // montagem do arquivo zerado
            $objectClassname = self::getObjectClassname($repositoryname);
            $object = new $objectClassname();
            $data = $object->GET_DATA($extraIncluded = true, $ctrlParametersIncluded = false, $referencesIncluded = true, $singleLevelArray = false);
            // deb($data);
            $defaultArray = [];
            $defaultEntryArray = [];
            foreach (array_keys($data) as $key) {

                { // evita o salvamento dos parametros de controle
                    $ctrl_left = _OFF_RepositoryReferences::ctrl_parameter_ini;
                    if (substr($key, 0, strlen($ctrl_left)) == $ctrl_left) {
                        continue;
                    }
                }
                $defaultEntryArray[$key] = '';
            }
            $defaultArray[0] = $defaultEntryArray;
            // deb($defaultArray);
            $repositoryCSV = RepositoryCSV::ArrayToCSV($defaultArray);
            self::saveRepositoryCSV($repositoryname, $repositoryCSV);
        }
    }

    // ==================================================================================================================================================
    // ============================================================================================================================================ ARRAY
    // ==================================================================================================================================================
    /**
     * obtem o conteudo da tabela em array
     *
     * @param string $repositoryname
     * @param string $conditions
     * @return string
     */
    private static function getRepositoryARRAY(string $repositoryname)
    {
        { // obtencao do conteudo do arquivo (em csv)
            $CSV = self::getRepositoryCSV($repositoryname);
            // deb($CSV);
        }
        { // conversao do conteudo em csv para array
            $repositoryARRAY = RepositoryCSV::CSVToArray($CSV);
            // deb($repositoryARRAY);
        }

        return $repositoryARRAY;
    }

    // ==============================================================================================================================-
    /**
     * converte o array em csv e o salva
     *
     * @param string $repositoryname
     * @param string $repositoryARRAY
     */
    private static function saveRepositoryARRAY(string $repositoryname, array $repositoryARRAY = [])
    {
        { // ordenacao dos itens pelo abs(id) independentemente de estarem deletados (-id)
          // orderby id asc
            $repositoryARRAY_KSORT = [];
            foreach ($repositoryARRAY as $id => $reg) {
                $repositoryARRAY_KSORT[abs($id)] = $reg;
            }
            ksort($repositoryARRAY_KSORT);
        }

        // array - csv
        $csv = RepositoryCSV::ArrayToCSV($repositoryARRAY_KSORT);
        // save
        self::saveRepositoryCSV($repositoryname, $csv);
    }

    // ==================================================================================================================================================
    // =========================================================================================================================================== OTHERS
    // ==================================================================================================================================================

    /**
     * obter o nome do arquivo da tabela (.csv)
     *
     * @param string $repositoryname
     * @return string
     */
    static function getRepositoryFilename(string $repositoryname): string
    {
        $repositoryname = strtolower($repositoryname);
        $return = self::foldername . DIRECTORY_SEPARATOR . $repositoryname . '.csv';
        $return = ServerHelp::fixds($return);
        return $return;
    }

    /**
     * verifica se o arquivo do repositorio existe
     *
     * @param string $repositoryname
     * @return bool
     */
    static function repositoryFileExist(string $repositoryname): bool
    {
        $RepositoryFilename = self::getRepositoryFilename($repositoryname);
        if (file_exists($RepositoryFilename)) {
            return true;
        } else {
            return false;
        }
    }

    // ==================================================================================================================================================
    // ==================================================================================================================================================
    // ==================================================================================================================================================
    static private function SortBy(array $array_messy, $conditions = '')
    {
        // deb($array_messy);
        // deb($conditions);
        { // condition analysis
            $conditions = trim($conditions);
            $conditions_ = explode(',', $conditions);
            $ordination = [];
            $sortKeyFormer = [];
            foreach ($conditions_ as $term) {
                $term = trim($term);
                $term_ = explode(' ', $term);
                if (sizeof($term_) == 1) {
                    $key = trim($term_[0]);
                    $orderTemp = 'ASC';
                } else if (sizeof($term_) == 2) {
                    $key = trim($term_[0]);
                    $orderTemp = strtoupper(trim($term_[1]));
                } else {
                    throw new Exception("Quantidade incorreta de termos de indexação na condição '$conditions'.");
                }
                if ($orderTemp != 'ASC' && $orderTemp != 'DESC') {
                    throw new Exception("Parâmetro de ordem de indexação incorreto ($conditions).");
                }

                // ----------------------------------
                $ordination[$key] = $orderTemp;
                // ----------------------------------
            }
            // deb($ordination);
        }

        $array = self::SortByIndexation($array_messy, $ordination);
        // deb($array);

        { // correcao dos identificadores como indice do array

            foreach ($array as $orderKey => $register) {
                // remove o registro correspondente e desordenado
                unset($array[$orderKey]);
                // inserer o registro na ordem correta
                if (! is_object($register)) {
                    $id = $register['id'];
                } else {
                    $id = $register->getId();
                }
                $array[$id] = $register;
            }
            // deb($array);
        }

        return $array;
    }

    static private function SortByIndexation(array $array_messy, array $ordination)
    {
        // deb($ordination, 0);
        // deb($array_messy);
        $array = [];
        foreach ($array_messy as $register) {

            // key
            $key = [];
            foreach ($ordination as $parameter => $order) {

                if (! is_object($register)) {
                    $parameterValue = $register[$parameter];
                } else {
                    $parameterValue = $register->{'get' . $parameter}();
                }

                if ($order == 'DESC') {
                    $parameterValue = Strings::str_inverter($parameterValue);
                }

                $key[] = $parameterValue;
            }
            $key = implode('.', $key);
            $array[$key] = $register;
        }
        ksort($array);
        // deb($array);
        return $array;
    }

    // ==================================================================================================================================================
    // ==================================================================================================================================================
    // ==================================================================================================================================================
    /**
     * Carrega os valores dos objetos referenciados por este
     */
    public function loadReferences()
    {
        $this_loaded = _OFF_RepositoryReferences::Load($this);
        $this->values = $this_loaded->values;
        $this->extra = $this_loaded->extra;
        $this->references = $this_loaded->references;
    }

    /**
     * Realiza a substituição dos valores dos campos que fazem
     * referencias a outros objetos com os valores destes
     * ATENÇÃO! Não utilize para SALVAMENTO posterior!!
     */
    public function replaceReferences($showFiels = [], $glue = '<br/>')
    {
        // $this->loadReferences();
        // deb($this,0);
        $data = $this->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = false, $referencesIncluded = false, $singleLevelArray = false);
        // deb($data,0);

        foreach ($data as $k => $v) {
            if (substr($k, - 3) == '_id' && strpos($k, '__') === false) {
                // deb($k,0);
                {
                    $setMethod = 'set' . ucfirst($k);
                    // deb($setMethod,0);
                }
                {
                    $tablename = substr($k, 0, strlen($k) - 3);
                    // deb($tablename);
                    $modelClassFullname = self::getObjectClassname($tablename);
                    // deb($modelClassFullname);
                    $obj = new $modelClassFullname($v);
                    $obj->loadReferences();
                    $obj = $obj->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = false, $referencesIncluded = false, $singleLevelArray = false);
                    // deb($obj,0);
                    {
                        $vNew = [];
                        if (sizeof($showFiels) > 0) {
                            foreach ($showFiels as $showField) {
                                $vNew[] = $obj[$showField];
                            }
                        } else {
                            foreach ($obj as $field => $fieldValue) {
                                if ($field == 'id' || substr($field, - 3) == '_id') {
                                    continue;
                                }
                                $vNew[] = "<span title='$field'>$fieldValue</span>";
                            }
                        }
                        $vNew = implode($glue, $vNew);
                    }
                }
                $this->$setMethod($vNew);
            }
        }
        // deb($this);
    }

    /**
     * Obtem um array com os possiveis modelos
     * referenciados por esta entidade
     *
     * @return array
     */
    public function getReferenciedModels(): array
    {
        $return = [];
        $parameters = $this->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = false, $referencesIncluded = true, $singleLevelArray = false);
        foreach (array_keys($parameters) as $column) {
            if (substr($column, - 3) == '_id') {
                $tablename = substr($column, 0, strlen($column) - 3);
                $return[$tablename] = $tablename;
            }
        }
        return $return;
    }

    static function get_filters($tablename)
    {
        $filters = [];
        {
            $objectClassName = self::getObjectClassname($tablename);
            $sample = new $objectClassName();
        }
        {
            $key_filters = $sample->key_filters;
            if (sizeof($key_filters) == 0) {
                $key_filters = array_keys($sample->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = false, $referencesIncluded = false, $singleLevelArray = false));
            }
        }
        {
            $key_names = $sample->key_names;
            foreach ($key_filters as $key_filter) {
                if (isset($key_names[$key_filter])) {
                    $filters[$key_filter] = $key_names[$key_filter];
                } else {
                    $filters[$key_filter] = ucfirst($key_filter);
                }
            }
            // deb($filters);
        }
        return $filters;
    }

    static function get_titles($tablename)
    {
        $titles = [];
        {
            $objectClassName = self::getObjectClassname($tablename);
            $sample = new $objectClassName();
        }
        {
            $key_names = $sample->key_names;
        }
        {
            $data_array = $sample->GET_DATA($extraIncluded = false, $ctrlParametersIncluded = false, $referencesIncluded = false, $singleLevelArray = false);
            foreach (array_keys($data_array) as $key) {

                if (isset($key_names[$key])) {
                    $titles[$key] = $key_names[$key];
                } else {
                    $titles[$key] = ucfirst($key);
                }
            }
            // deb($titles);
        }
        return $titles;
    }

    public function getValueKeys()
    {
        return array_keys($this->values);
    }

    public function getValuesKeysNames()
    {
        return array_keys($this->key_names);
    }

    // ==================================================================================================================================================
    // =========================================================================================================================================== START
    // ==================================================================================================================================================
    static function inicializar()
    {
        try {

            { // obtencao do nome do repositorio
                $called_class = get_called_class();
                $class_sample = new $called_class();
                $repositoryname = $class_sample->getRepositoryname();
                // deb($repositoryname);
            }

            if (! self::repositoryFileExist($repositoryname)) {
                ProcessResult::setWarning("Não foi encontrado o arquivo do repositorio para o modelo '$repositoryname'.");

                // cria o arquivo repositorial (vazio)
                ProcessResult::setWarning("Arquivo inicial do repositorio '$repositoryname' não encontrado.");
                self::getRepositoryLength($repositoryname);
                ProcessResult::setSuccess("Repositorio '$repositoryname' inicializado sem registros (VAZIO).");
            } else {
                ProcessResult::setSuccess("O arquivo do repositorio '$repositoryname' já foi inicializado. Não há nenhum procedimento a ser realizado.");
            }

            // inicializacao do modelo
            parent::inicializar();
        } catch (Exception $e) {
            ProcessResult::setError($e);
        }
    }
}

?>