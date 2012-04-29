<?php
/**
 * Image active record class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2011-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @since 0.5
 */

/**
 * This is the model class for table "Image".
 *
 * The followings are the available columns in table 'Image':
 * @property integer $id
 * @property string $name
 * @property string $path
 * @property string $extension
 * @property string $filename
 * @property integer $byteSize
 * @property string $mimeType
 * @property string $created
 * @property integer $deleted
 */
class Image extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className the class name.
	 * @return Image the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'image';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('extension, filename, byteSize, mimeType','required'),
			array('byteSize','numerical','integerOnly'=>true),
			array('name, path, extension, filename, mimeType, created','length','max'=>255),
			array('id, name, path, extension, filename, byteSize, mimeType, created','safe','on'=>'search'),
		);
	}


	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => Img::t('core','Id'),
			'name' => Img::t('core','Name'),
			'path' => Img::t('core','Path'),
			'extension' => Img::t('core','Extension'),
			'filename' => Img::t('core','Filename'),
			'byteSize' => Img::t('core','Byte Size'),
			'mimeType' => Img::t('core','Mime Type'),
			'created' => Img::t('core','Created'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('path',$this->path,true);
		$criteria->compare('extension',$this->extension,true);
		$criteria->compare('filename',$this->filename,true);
		$criteria->compare('byteSize',$this->byteSize);
		$criteria->compare('mimeType',$this->mimeType,true);
		$criteria->compare('created',$this->created,true);

		return new CActiveDataProvider(get_class($this),array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Renders this image.
	 * @param string $version the image version to render.
	 * @param string $alt the alternative text.
	 * @param array $htmlOptions the html options.
	 */
	public function render($version,$alt='',$htmlOptions=array())
	{
		$src = Yii::app()->image->getURL($this->id,$version);
		echo CHtml::image($src,$alt,$htmlOptions);
	}

	/**
	 * @return string the path for this image.
	 */
	public function getPath()
	{
		return !empty($this->path) ? $this->path.'/' : '';
	}

	/**
	 * @return string the image file name.
	 */
	public function resolveFilename()
	{
		return (!empty($this->name) ? $this->name : $this->id).'.'.$this->extension;
	}
}
