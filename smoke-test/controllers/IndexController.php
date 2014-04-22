<?php

class SmokeTest_IndexController extends Zend_Controller_Action
{
    private $config;

    public function init()
    {
        $this->config = new Zend_Config($this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions());
        $this->disableView();
    }

    public function indexAction()
    {
        // One day we pushed and just forgot to install the database schema.
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($db && 0 === count($db->listTables())) {
            throw new Exception('No tables in the databases.');
        }

        // One day we used implicit database connection charset and it
        // double-encoded utf-8 characters. Effectively screwing our friday.
        if ($this->getConfig('resources.db') && !$this->getConfig('resources.db.params.charset')) {
            throw new Exception('Missing "resources.db.params.charset" in application.ini.');
        }

        // One day we pushed on a server using an old version of PHP.
        $phpversion = $this->getConfig('smoke-test.phpversion', '5.3');
        if (false === version_compare(PHP_VERSION, $phpversion, '>=')) {
            throw new Exception('PHP version outdated: ' . PHP_VERSION . " >= $phpversion");
        }

        // One day we pushed on a server without the gd extension we needed.
        $extensions = $this->getConfig('smoke-test.extensions', array('iconv'));
        foreach ($extensions as $name) {
            if (false === extension_loaded($name)) {
                throw new Exception("PHP extension not loaded: $name");
            }
        }

        // One day we pushed and forgot to make the `data/uploads` directory writable.
        $writables = $this->getConfig('smoke-test.writables', $this->defaultWritableDirs());
        foreach ($writables as $filename) {
            if (false === is_writable($filename)) {
                throw new Exception("Filename not writable: $filename");
            }
        }

        // One day we pushed and forgot to include the compiled/minified JavaScript lib.
        $readables = $this->getConfig('smoke-test.readables', array());
        foreach ($readables as $filename) {
            if (false === is_readable($filename)) {
                throw new Exception("Filename not readable: $filename");
            }
        }

        // Another day we pushed and forgot to recompile that JavaScript lib.
        $latests = $this->getConfig('smoke-test.latests', array());
        foreach ($latests as $latest) {
            list($out, $src) = $latest->toArray();
            if (self::findLatestModificationTime($out) < self::findLatestModificationTime($src)) {
                throw new Exception("A file in '$src' was modified after '$out'");
            }
        }

        // One day we did not configure smtp
        $transport = $this->getConfig('resources.mail.transport');
        if ($transport && 'smtp' === $transport->type) {
            $this->createSmtpConnection($transport)->helo();
        }

        // One day .. no. This one actually never happended, we are lucky.
        if (ini_get('magic_quotes_gpc')) {
            throw new Exception("Evil magic_quotes_gpc must be disabled");
        }

        echo 'ok';
    }

    /**
     * Shortcut to disable the Zend_View sub-system.
     */
    private function disableView()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        if (Zend_Controller_Action_HelperBroker::hasHelper('layout')) {
            $this->_helper->layout->disableLayout();
        }
    }

    /**
     * A walking helper for Zend_Config::get.
     *
     * @param $path The key in application.ini, eg. `resources.db.adapter`
     * @param $default $default value if key or any parents are not there
     */
    private function getConfig($path, $default = null)
    {
        $config = $this->config;
        foreach (explode('.', $path) as $part) {
            $config = $config->get($part);
            if (is_null($config))
                return $default;
        }
        return $config;
    }

    /**
     * The list of default must-be-writable folders;
     * taken shamefully from the Zend Recommended Project Directory Structure.
     *
     * @see http://framework.zend.com/manual/1.12/en/project-structure.project.html
     */
    private static function defaultWritableDirs()
    {
        $names = array('data/cache', 'data/indexes', 'data/logs', 'data/sessions', 'data/uploads', 'temp');
        $writables = array();
        foreach ($names as $name) {
            $path = APPLICATION_PATH . "/../$name";
            if (is_dir($path)) {
                $writables[] = $path;
            }
        }
        return $writables;
    }

    /**
     * Find the latest modification time in a directory tree.
     *
     * @param $basename Path where to start searching
     */
    private static function findLatestModificationTime($basename)
    {
        return array_reduce(array_map('filemtime', self::listFiles($basename)), 'max', 0);
    }

    /**
     * Recursively list files in a directory.
     *
     * @note This function does not belong to this class.
     */
    private static function listFiles($basename)
    {
        if (is_dir($basename)) {
            $files = array();
            if ($handle = opendir($basename)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        $fullname = "$basename/$file";
                        if (is_dir($fullname)) {
                            $files = array_merge($files, self::listFiles($fullname));
                        } else {
                            $files[] = $fullname;
                        }
                    }
                }
                closedir($handle);
            }
            return $files;
        } elseif (is_file($basename)) {
            return array($basename);
        } else {
            return array();
        }
    }

    /**
     * Abstract the details of creating an Smtp connection.
     *
     * @param $config the `resources.mail.transport`.
     */
    private function createSmtpConnection(Zend_Config $config)
    {
        $connectionClass = 'Zend_Mail_Protocol_Smtp';
        if ($config->auth) {
            $connectionClass .= '_Auth_' . ucwords($config->auth);
        }

        $connection = new $connectionClass($config->host, $config->port, $config->toArray());
        $connection->connect();
        return $connection;
    }
}
