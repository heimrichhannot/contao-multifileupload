<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\MultiFileUpload\Test;

use Contao\System;
use Contao\Widget;
use HeimrichHannot\Ajax\Ajax;
use HeimrichHannot\Ajax\AjaxAction;
use HeimrichHannot\Ajax\Exception\AjaxExitException;
use HeimrichHannot\Haste\Util\Url;
use HeimrichHannot\MultiFileUpload\FormMultiFileUpload;
use HeimrichHannot\MultiFileUpload\MultiFileUpload;
use HeimrichHannot\Request\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadTest extends TestCase
{
    static private $testfile = [
        'file   name.zip',
        'საბეჭდი_მანქანა.png',
        '.~file   name#%&*{}:<>?+|"\'.zip',
        'file___name.zip',
        'file...name..zip',
        'file name.zip',
        'file--.--.-.--name.zip',
        'file---name.zip',
    ];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        foreach (static::$testfile as $file)
        {
            @copy(UNIT_TESTING_FILES . '/filename.zip', UNIT_TESTING_FILES . '/'.$file);
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        foreach (static::$testfile as $file)
        {
            unlink(UNIT_TESTING_FILES.'/'.$file);
        }
    }

    protected function setUp()
    {
        System::loadLanguageFile('default','de');
        // reset request parameter bag
        Request::set(new \Symfony\Component\HttpFoundation\Request());
    }


    /**
     * test upload controller against cross-site request
     *
     * @test
     */
    public function testUploadHTMLInjection()
    {
        $strAction = AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD);

        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . $strAction, 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        @copy(UNIT_TESTING_FILES . '/file   name.zip', UNIT_TESTING_FILES . '/tmp/file   name.zip');

        $arrFiles = [
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file   name.zip', // Name of the sent file
                '"b<marquee onscroll=alert(1)>file   name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
        ];

        $objRequest->files->add(['files' => $arrFiles[0]]);

        Request::set($objRequest);



        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'zip',
                'fieldType'    => 'radio',
            ]
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');
//        fwrite(STDERR, print_r($arrAttributes, TRUE));


        try {
            $uploader = new FormMultiFileUpload($arrAttributes, true);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());
            $this->assertSame('bmarquee-onscrollalert1file-name.zip', $objJson->result->data->filenameSanitized);
        }
    }

    /**
     * test upload controller against cross-site request
     *
     * @test
     */
    public function testInvalidAjaxUploadToken()
    {
        $strAction = AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD);
        $strAction = Url::removeQueryString([Ajax::AJAX_ATTR_TOKEN], $strAction);
        $strAction = Url::addQueryString(Ajax::AJAX_ATTR_TOKEN . '=' . 12355456, $strAction);

        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . $strAction, 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        @copy(UNIT_TESTING_FILES . '/file   name.zip', UNIT_TESTING_FILES . '/tmp/file   name.zip');
        @copy(UNIT_TESTING_FILES . '/file---name.zip', UNIT_TESTING_FILES . '/tmp/file---name.zip');
        @copy(UNIT_TESTING_FILES . '/file--.--.-.--name.zip', UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip');
        @copy(UNIT_TESTING_FILES . '/file...name..zip', UNIT_TESTING_FILES . '/tmp/file...name..zip');
        @copy(UNIT_TESTING_FILES . '/file___name.zip', UNIT_TESTING_FILES . '/tmp/file___name.zip');
        @copy(UNIT_TESTING_FILES . '/.~file   name#%&*{}:<>?+|"\'.zip', UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip');

        // simulate upload of php file hidden in an image file
        $arrFiles = [
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file   name.zip', // Name of the sent file
                'file   name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file---name.zip', // Name of the sent file
                'file---name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip', // Name of the sent file
                'file--.--.-.--name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file...name..zip', // Name of the sent file
                'file...name..zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file___name.zip', // Name of the sent file
                'file___name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip', // Name of the sent file
                '.~file   name#%&*{}:<>?+|"\'.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
        ];

        $objRequest->files->add(['files' => $arrFiles]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'zip',
                'maxFiles'     => 2,
                'fieldType'    => 'checkbox',
            ],
        ];

        $arrAttributes = Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());
            $this->assertSame('Invalid ajax token.', $objJson->message);
        }
    }

    /**
     * test upload controller against cross-site disk flooding
     *
     * @test
     */
    public function testDiskFlooding()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        @copy(UNIT_TESTING_FILES . '/file   name.zip', UNIT_TESTING_FILES . '/tmp/file   name.zip');
        @copy(UNIT_TESTING_FILES . '/file---name.zip', UNIT_TESTING_FILES . '/tmp/file---name.zip');
        @copy(UNIT_TESTING_FILES . '/file--.--.-.--name.zip', UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip');
        @copy(UNIT_TESTING_FILES . '/file...name..zip', UNIT_TESTING_FILES . '/tmp/file...name..zip');
        @copy(UNIT_TESTING_FILES . '/file___name.zip', UNIT_TESTING_FILES . '/tmp/file___name.zip');
        @copy(UNIT_TESTING_FILES . '/.~file   name#%&*{}:<>?+|"\'.zip', UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip');

        // simulate upload of php file hidden in an image file
        $arrFiles = [
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file   name.zip', // Name of the sent file
                'file   name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file---name.zip', // Name of the sent file
                'file---name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip', // Name of the sent file
                'file--.--.-.--name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file...name..zip', // Name of the sent file
                'file...name..zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file___name.zip', // Name of the sent file
                'file___name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip', // Name of the sent file
                '.~file   name#%&*{}:<>?+|"\'.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
        ];

        $objRequest->files->add(['files' => $arrFiles]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'zip',
                'maxFiles'     => 2,
                'fieldType'    => 'checkbox',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());

            $this->assertSame('Bulk file upload violation.', $objJson->message);
        }
    }

    /**
     * @test
     */
    public function testSanitizeFileNames()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        // prevent test file removal
        @copy(UNIT_TESTING_FILES . '/file   name.zip', UNIT_TESTING_FILES . '/tmp/file   name.zip');
        @copy(UNIT_TESTING_FILES . '/file---name.zip', UNIT_TESTING_FILES . '/tmp/file---name.zip');
        @copy(UNIT_TESTING_FILES . '/file--.--.-.--name.zip', UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip');
        @copy(UNIT_TESTING_FILES . '/file...name..zip', UNIT_TESTING_FILES . '/tmp/file...name..zip');
        @copy(UNIT_TESTING_FILES . '/file___name.zip', UNIT_TESTING_FILES . '/tmp/file___name.zip');
        @copy(UNIT_TESTING_FILES . '/.~file   name#%&*{}:<>?+|"\'.zip', UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip');


        // simulate upload of php file hidden in an image file
        $arrFiles = [
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file   name.zip', // Name of the sent file
                'file   name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file---name.zip', // Name of the sent file
                'file---name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file--.--.-.--name.zip', // Name of the sent file
                'file--.--.-.--name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file...name..zip', // Name of the sent file
                'file...name..zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/file___name.zip', // Name of the sent file
                'file___name.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
            new UploadedFile(// Path to the file to send
                UNIT_TESTING_FILES . '/tmp/.~file   name#%&*{}:<>?+|"\'.zip', // Name of the sent file
                '.~file   name#%&*{}:<>?+|"\'.zip', // mime type
                'application/zip', // size of the file
                48140, null, true),
        ];

        $objRequest->files->add(['files' => $arrFiles]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'zip',
                'fieldType'    => 'checkbox',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes, true);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());

            $this->assertSame('file-name.zip', $objJson->result->data[0]->filenameSanitized);
            $this->assertSame('file-name.zip', $objJson->result->data[1]->filenameSanitized);
            $this->assertSame('file-name.zip', $objJson->result->data[2]->filenameSanitized);
            $this->assertSame('file-name.zip', $objJson->result->data[3]->filenameSanitized);
            $this->assertSame('file___name.zip', $objJson->result->data[4]->filenameSanitized);
            $this->assertSame('file-name.zip', $objJson->result->data[5]->filenameSanitized);
        }
    }

    /**
     * @test
     */
    public function testMaliciousFileUploadOfInvalidCharactersInFileName()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        // prevent test file removal
        @copy(UNIT_TESTING_FILES . '/საბეჭდი_მანქანა.png', UNIT_TESTING_FILES . '/tmp/საბეჭდი_მანქანა.png');

        // simulate upload of php file hidden in an image file
        $file = new UploadedFile(// Path to the file to send
            UNIT_TESTING_FILES . '/tmp/საბეჭდი_მანქანა.png', // Name of the sent file
            'საბეჭდი_მანქანა.png', // mime type
            'image/png', // size of the file
            64693, null, true);

        $objRequest->files->add(['files' => $file]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'jpg,jpeg,gif,png',
                'fieldType'    => 'radio',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes);
            $objUploader->upload();
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());

            $this->assertSame('sabejdi_mankhana.png', $objJson->result->data->filenameSanitized);
        }
    }

    /**
     * @test
     */
    public function testUploadCSVFile()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        // prevent test file removal
        @copy(UNIT_TESTING_FILES . '/data.csv', UNIT_TESTING_FILES . '/tmp/data.csv');

        // simulate upload of php file hidden in an image file
        $file = new UploadedFile(// Path to the file to send
            UNIT_TESTING_FILES . '/tmp/data.csv', // Name of the sent file
            'data.csv', // mime type
            'text/csv', // size of the file
            7006, null, true);

        $objRequest->files->add(['files' => $file]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'csv',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes, true);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());

            $this->assertNull($objJson->result->data->error);
        }
    }

    /**
     * @test
     */
    public function testMaliciousFileUploadOfDisguisedPhpFile()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        // prevent test file removal
        @copy(UNIT_TESTING_FILES . '/cmd_test.php.jpg', UNIT_TESTING_FILES . '/tmp/cmd_test.php.jpg');

        // simulate upload of php file hidden in an image file
        $file = new UploadedFile(// Path to the file to send
            UNIT_TESTING_FILES . '/tmp/cmd_test.php.jpg', // Name of the sent file
            'cmd_test.php.jpg', // mime type
            'image/jpeg', // size of the file
            652, null, true);

        $objRequest->files->add(['files' => $file]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'jpg,jpeg,gif,png',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes);
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());
            fwrite(STDERR, print_r($objJson, TRUE));

            $this->assertSame('Unerlaubter Dateityp: text/x-php', $objJson->result->data->error);
            $this->assertSame('cmd_test-php.jpg', $objJson->result->data->filenameSanitized);
        }
    }

    /**
     * @test
     */
    public function testMaliciousFileUploadOfInvalidTypes()
    {
        $objRequest = \Symfony\Component\HttpFoundation\Request::create('http://localhost' . AjaxAction::generateUrl(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD), 'post');
        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request
        $objRequest->request->set('requestToken', \RequestToken::get());
        $objRequest->request->set('files', []);

        // prevent test file removal
        @copy(UNIT_TESTING_FILES . '/cmd_test.php.jpg', UNIT_TESTING_FILES . '/tmp/cmd_test.php.jpg');
        @copy(UNIT_TESTING_FILES . '/cmd_test.php', UNIT_TESTING_FILES . '/tmp/cmd_test.php');
        @copy(UNIT_TESTING_FILES . '/cmd_test1.php', UNIT_TESTING_FILES . '/tmp/cmd_test1.php');

        $file = new UploadedFile(// Path to the file to send
            UNIT_TESTING_FILES . '/tmp/cmd_test.php', // Name of the sent file
            'cmd_test.php', // mime type
            'text/x-php', // size of the file
            652, null, true);

        $file2 = new UploadedFile(// Path to the file to send
            UNIT_TESTING_FILES . '/tmp/cmd_test1.php', // Name of the sent file
            'cmd_test1.php', // mime type
            'text/x-php', // size of the file
            652, null, true);

        $objRequest->files->add(['files' => [$file, $file2]]);

        Request::set($objRequest);

        $arrDca = [
            'inputType' => 'multifileupload',
            'eval'      => [
                'uploadFolder' => UNIT_TESTING_FILES . 'uploads/',
                'extensions'   => 'jpg,jpeg,gif,png',
                'fieldType'    => 'checkbox',
            ],
        ];

        $arrAttributes = \Widget::getAttributesFromDca($arrDca, 'files');

        try {
            $objUploader = new FormMultiFileUpload($arrAttributes);
            $objUploader->upload();
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e) {
            $objJson = json_decode($e->getMessage());

            $this->assertSame('Unerlaubte Dateiendung: php', $objJson->result->data[0]->error);
            $this->assertSame('cmd_test.php', $objJson->result->data[0]->filenameSanitized);

            $this->assertSame('Unerlaubte Dateiendung: php', $objJson->result->data[1]->error);
            $this->assertSame('cmd_test1.php', $objJson->result->data[1]->filenameSanitized);
        }
    }
}
