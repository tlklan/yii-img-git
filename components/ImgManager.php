<?php
/**
 * Image manager class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2011-
 * @license http://www.opensource.org/licenses/bsd-license New BSD License
 * @since 0.5
 */

/**
 * Provides easy image manipulation with the help of the excellent PHP Thumbnailer library.
 * @see http://phpthumb.gxdlabs.com/
 */

require_once(dirname(__FILE__).'/../vendors/phpthumb/ThumbLib.inc.php'); // Yii::import() will not work in this case.

class ImgManager extends CApplicationComponent
{
	/**
	 * PhpThumb options that are passed to the ThumbFactory.
	 * Default values are the following:
	 *
	 * <code>
	 * array(
	 *     'resizeUp' => false,
	 *     'jpegQuality' => 100,
	 *     'correctPermissions' => false,
	 *     'preserveAlpha' => true,
	 *     'alphaMaskColor'	=> array(255, 255, 255),
	 *     'preserveTransparency' => true,
	 *     'transparencyMaskColor' => array(0, 0, 0),
	 * );
	 * </code>
	 *
	 * @property array
	 */
	public $thumbOptions=array();
	/**
	 * @property string the relative path where to store images.
	 */
	public $imagePath='files/images/';
	/**
	 * @property array the image versions.
	 */
	public $versions=array();
	/**
	 * @property string the base path.
	 */
	protected $_basePath;
	/**
	 * @property string the image version path.
	 */
	protected $_versionBasePath;

	private static $_thumbOptions=array(); // needed for the static factory-method
	private static $_imagePath;

	/**
	 * Initializes the component.
	 */
	public function init()
	{
		self::$_thumbOptions=$this->thumbOptions;
		self::$_imagePath=$this->getImagePath(true);

		parent::init();
	}

	/**
	 * Returns the URL for a specific image.
	 * @param string $id the image id.
	 * @param string $version the name of the image version.
	 * @param boolean $absolute whether or not to get an absolute URL.
	 * @return string the URL.
	 * @throws CException if the version is not defined.
	 */
	public function getURL($id,$version,$absolute=false)
	{
		if(isset($this->versions[$version]))
		{
			$image = $this->loadModel($id);
			$filename=$this->resolveFileName($image);
			$path=$this->getVersionPath($version);
			return Yii::app()->request->getBaseUrl($absolute).'/'.$path.$filename;
		}
		else
			throw new ImgException(Img::t('error','Failed to get image URL! Version is unknown.'));
	}

	/**
	 * Saves a new image.
	 * @param string $name the image name. Available since 1.2.0
	 * @param CUploadedFile $file the uploaded image.
	 * @return Image the image record.
	 * @throws ImageException if saving the image record or file fails.
	 */
	public function save($name,$file)
	{
		$trx=Yii::app()->db->beginTransaction();

		try
		{
			$image=new Image();
			$image->name=$this->normalizeString($name);
			$image->extension=strtolower($file->getExtensionName());
			$image->filename=$file->getName();
			$image->byteSize=$file->getSize();
			$image->mimeType=$file->getType();
			$image->created=new CDbExpression('NOW()');

			if($image->save()===false)
				throw new ImgException(Img::t('error','Failed to save image! Record could not be saved.'));

			$filename=$this->resolveFileName($image);
			$path=$this->getImagePath(true);

			if($file->saveAs($path.$filename)===false)
				throw new ImgException(Img::t('error','Failed to save image! File could not be saved.'));

			$trx->commit();
			return $image;
		}
		catch(CException $e)
		{
			$trx->rollback();
			throw $e;
		}
	}

	/**
	 * Deletes a specific image.
	 * @param $id the image id.
	 * @return boolean whether the image was deleted.
	 * @throws ImgException if the image cannot be deleted.
	 */
	public function delete($id)
	{
		/** @var Image $image */
		$image = Image::model()->findByPk($id);

		if($image instanceof Image)
		{
			$fileName=$this->resolveFileName($image);
			$filePath=$this->getImagePath(true).$fileName;

			if($image->delete()===false)
				throw new ImgException(Img::t('error', 'Failed to delete image! Record could not be deleted.'));

			if(file_exists($filePath)!==false && unlink($filePath)===false)
				throw new ImgException(Img::t('error', 'Failed to delete image! File could not be deleted.'));

			foreach($this->versions as $version=>$config)
				$this->deleteVersion($image, $version);
		}
		else
			throw new ImgException(Img::t('error', 'Failed to delete image! Record could not be found.'));
	}

	/**
	 * Deletes a specific image version.
	 * @param Image $image the image model.
	 * @param string $version the image version.
	 * @return boolean whether the image was deleted.
	 * @throws ImgException if the image cannot be deleted.
	 */
	protected function deleteVersion($image,$version)
	{
		if(isset($this->versions[$version]))
		{
			$filePath=$this->resolveImageVersionPath($image,$version);
			if(file_exists($filePath)!==false && unlink($filePath)===false)
				throw new ImgException(Img::t('error', 'Failed to delete the image version! File could not be deleted.'));
		}
		else
			throw new ImgException(Img::t('error', 'Failed to delete image version! Version is unknown.'));
	}

	/**
	 * Loads a thumb of a specific image.
	 * @param integer $id the image id.
	 * @return ThumbBase
	 */
	public function loadThumb($id)
	{
		$image=$this->loadModel($id);

		if($image!==null)
		{
			$fileName=$this->resolveFileName($image);
			$thumb=self::thumbFactory($fileName);
			return $thumb;
		}
		else
			return null;
	}

	/**
	 * Loads a specific image model.
	 * @param integer $id the image id.
	 * @return Image
	 */
	public function loadModel($id)
	{
		return Image::model()->findByPk($id);
	}

	/**
	 * Creates a new version of a specific image.
	 * @param integer $id the image id.
	 * @param string $version the image version.
	 * @return ThumbBase
	 */
	public function createVersion($id,$version)
	{
		if(isset($this->versions[$version]))
		{
			$image=$this->loadModel($id);

			if($image!=null)
			{
				$fileName=$this->resolveFileName($image);
				$thumb=self::thumbFactory($fileName);
				$options=ImgOptions::create($this->versions[$version]);
				$thumb->applyOptions($options);
				$path=$this->getVersionPath($version,true);
				return $thumb->save($path.$fileName);
			}
			else
				throw new ImgException(Img::t('error','Failed to create version! Image could not be found.'));
		}
		else
			throw new ImgException(Img::t('error','Failed to create version! Version is unknown.'));
	}

	/**
	 * Returns the images path.
	 * @param boolean $absolute whether or not the path should be absolute.
	 * @return string the path.
	 */
	public function getImagePath($absolute=false)
	{
		$path='';

		if($absolute===true)
			$path.=$this->getBasePath();

		return $path.$this->imagePath;
	}

	/**
	 * Returns the version specific path.
	 * @param string $version the name of the image version.
	 * @param boolean $absolute whether or not the path should be absolute.
	 * @return string the path.
	 */
	protected function getVersionPath($version,$absolute=false)
	{
		$path=$this->getVersionBasePath($absolute).$version.'/';

		// Might be a new version so we need to create the path if it doesn't exist.
		if(!file_exists($path))
			mkdir($path);

		return $path;
	}

	/**
	 * Returns the original image file name.
	 * @param Image $image the image model.
	 * @return string the file name.
	 */
	protected function resolveFileName($image)
	{
		if($image instanceof Image)
		{
			if(!empty($image->name))
				return $image->name.'-'.$image->id.'.'.$image->extension; // since 1.2.0
			else
				return $image->id.'.'.$image->extension; // backwards compatibility
		}
		else
			return null;
	}

	/**
	 * Returns the path to a specific image version.
	 * @param Image $image the image model.
	 * @param string $version the image version.
	 * @return string the path.
	 */
	protected function resolveImageVersionPath($image,$version)
	{
		if($image instanceof Image)
			return $this->getVersionPath($version,true).$this->resolveFileName($image);
		else
			return null;
	}

	/**
	 * Returns the base path.
	 * @return string the path.
	 */
	protected function getBasePath()
	{
		if($this->_basePath!==null)
			return $this->_basePath;
		else
			return $this->_basePath=realpath(Yii::app()->basePath.'/../').'/';
	}

	/**
	 * Returns the image version path.
	 * @param boolean $absolute whether or not the path should be absolute.
	 * @return string the path.
	 */
	protected function getVersionBasePath($absolute=false)
	{
		$path='';

		if($absolute===true)
			$path.=$this->getBasePath();

		if($this->_versionBasePath!==null)
			$path.=$this->_versionBasePath;
		else
			$path.=$this->_versionBasePath = $this->getImagePath().'versions/';

		return $path;
	}

	/**
	 * Normalizes the given string by replacing special characters. å=>a, é=>e, ö=>o, etc.
	 * @param string the string to normalize.
	 * @return string the normalized string.
	 * @since 1.2.0
	 */
	protected function normalizeString($string)
	{
        return preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i','$1',htmlentities($string,ENT_QUOTES,'UTF-8'));
	}

	/**
	 * Creates a new image.
	 * @param string $fileName the file name.
	 * @return ImgThumb
	 */
	protected static function thumbFactory($fileName)
	{
		$phpThumb=PhpThumbFactory::create(self::$_imagePath.$fileName,self::$_thumbOptions);
		return new ImgThumb($phpThumb);
	}
}
