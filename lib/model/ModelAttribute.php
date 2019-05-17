<?php
namespace manguto\cms5\lib\model;

use manguto\cms5\lib\Exception;

class ModelAttribute
{

    private $name;

    private $type;

    private $nature;

    private $length;

    private $value;

    private $unit;

    private $encrypted;

    // ------------------------------------------------------------------------------------------------------------------------
    const TYPE_CHAR = 'char';

    const TYPE_VARCHAR = 'varchar';

    const TYPE_TEXT = 'text';

    const TYPE_INT = 'int';

    const TYPE_FLOAT = 'float';

    const TYPE_DATE = 'date';

    const TYPE_DATETIME = 'datetime';

    const TYPE_TIMESTAMP = 'timestamp';

    const TYPE_TIME = 'time';

    const TYPE_BOOLEAN = 'boolean';

    // -----------------------------------------------------------------
    const NATURE_DEFAULT = 'default';

    const NATURE_REFERENCE_SINGLE = 'reference_single';

    const NATURE_REFERENCE_MULTIPLE = 'reference_multiple';

    const NATURE_EMAIL = 'email';

    // ########################################################################################################################
    // ########################################################################################################################
    // ########################################################################################################################
    public function __construct($attributeName, bool $checkAttributeName = true)
    {
        { // nome do atributo
            if ($checkAttributeName) {
                $this->checkAttributeName($attributeName);
            }
            $this->setName($attributeName);
        }
        // tipo padrao
        $this->type = self::TYPE_VARCHAR;

        // natureza padrao
        $this->nature = self::NATURE_DEFAULT;

        // valor padrao
        $this->value = '';
    }

    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> GETTERS & SETTERS
    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> GETTERS & SETTERS
    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> GETTERS & SETTERS
    private function setName($name)
    {
        $this->name = $name;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    public function setType($type)
    {
        $constant_name = 'TYPE_' . strtoupper($type);
        // deb($constant_name);
        if (! defined("self::$constant_name")) {
            throw new Exception("Definição de tipo de atributo incorreto ou não encontrado ('$type').");
        }
        $this->type = $type;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    public function setNature($nature)
    {
        $constant_name = 'NATURE_' . strtoupper($nature);
        // deb($constant_name);
        if (! defined("self::$constant_name")) {
            throw new Exception("Definição de natureza de atributo incorreto ou não encontrado ('$nature').");
        }
        $this->nature = $nature;
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    /**
     * retorna o valor do atributo
     *
     * @param mixed $value
     */
    public function getValue()
    {
        return $this->shapeValue($this->value);
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    /**
     * define o valor do atributo
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $this->shapeValue($value);
    }

    /**
     * molda o valor informado de acordo com o tipo do atributo atual
     *
     * @param mixed $value
     * @return mixed
     */
    private function shapeValue($value)
    {
        // ---------------------------------------------------------------
        /**
         * const TYPE_CHAR = 'char';
         * const TYPE_VARCHAR = 'varchar';
         * const TYPE_TEXT = 'text';
         * const TYPE_INT = 'int';
         * const TYPE_FLOAT = 'float';
         * const TYPE_DATE = 'date';
         * const TYPE_DATETIME = 'datetime';
         * const TYPE_TIMESTAMP = 'timestamp';
         * const TYPE_TIME = 'time';
         * const TYPE_BOOLEAN = 'boolean';
         */
        // ---------------------------------------------------------------
        switch ($this->getType()) {
            case self::TYPE_INT:
                return intval($value);
                break;

            case self::TYPE_FLOAT:
                return floatval($value);
                break;

            case self::TYPE_BOOLEAN:
                return boolval($value);
                break;

            default:
                return strval($value);
                ;
                break;
        }
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    // magic methods GET & SET geral
    public function __call(string $methodName, $args)
    {
        // metodo aplicado (solicitado)
        $method_nature = substr($methodName, 0, 3);

        // garimpa o nome do parametro
        $attributeName = strtolower(substr($methodName, 3));

        if ($method_nature == 'get') {

            return $this->$attributeName;
        } elseif ($method_nature == 'set') {

            $value = $args[0];

            $this->$attributeName = $value;
        } else {

            throw new Exception("Método não encontrado ou incorreto ($methodName()).");
        }
    }

    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< GETTERS & SETTERS
    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< GETTERS & SETTERS
    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< GETTERS & SETTERS
    /**
     * obtem uma representacao do atributo
     * atraves de uma "string_alizacao"
     * do seu valor
     *
     * @return string
     */
    public function __toString()
    {
        $return = $this->getValue();
        $return = strval($return);
        // deb($return);

        return $return;
    }

    // ########################################################################################################################
    // ########################################################################################################################
    // ########################################################################################################################
    /**
     * verifica se o atributo precisa de "aspas"
     * conforme o tipo do mesmo
     *
     * @return boolean
     */
    public function checkQuotesWrap()
    {
        $notQuoteWrapTypes = [
            self::TYPE_FLOAT,
            self::TYPE_INT
        ];
        return ! in_array($this->getType(), $notQuoteWrapTypes);
    }

    // ########################################################################################################################
    // ########################################################################################################################
    // ########################################################################################################################

    /**
     * verifica se o nome pode ser utilizado como atributo
     *
     * @param string $attributeName
     * @throws Exception
     */
    private function checkAttributeName(string $attributeName)
    {
        if (in_array($attributeName, Model::fundamentalAttributes)) {
            throw new Exception("Foi definido um nome de atributo reservado para um atributo fundamental do modelo ($attributeName). Por favor, escolha outro e tente novamente.");
        }
    }

    // ########################################################################################################################
    // ########################################################################################################################
    // ########################################################################################################################

    /**
     * recebe um array de parametros brutos e o converte em um array de ModelAttributes
     *
     * @param array $attributeArray
     * @param bool $checkAttributeName
     * @return array
     */
    static function Convert_ParameterDataArray_to_ModelAttributeArray(array $attributeArray, bool $checkAttributeName = true): array
    {
        $ModelAttributeArray = [];

        foreach ($attributeArray as $attributeName => $parameters) {
            // cria o atributo
            // deb($attributeName,0); deb($parameters,0);
            $ModelAttribute = new ModelAttribute($attributeName, $checkAttributeName);
            foreach ($parameters as $parameterName => $parameterValue) {
                $setMethod = 'set' . ucfirst($parameterName);
                // define os parametros
                $ModelAttribute->$setMethod($parameterValue);
            }
            // salva no modelo
            $ModelAttributeArray[$attributeName] = $ModelAttribute;
        }

        return $ModelAttributeArray;
    }

    // ########################################################################################################################
    // ########################################################################################################################
    // ########################################################################################################################
}

?>