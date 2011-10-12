<?php
/**
 * Image behavior class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2011-
 * @license http://www.opensource.org/licenses/bsd-license New BSD License
 * @since 1.1.0
 */
class ImgRecordBehavior extends CBehavior
{
	/**
	 * Saves the image for the owner of this behavior.
	 * @param CUploadedFile $file the uploaded file.
	 */
	public function saveImage($file)
	{
		$image = Yii::app()->image->save($file);

		if ($image !== null && $this->owner->hasAttribute('imageId'))
			$this->owner->imageId = $image->id;
	}

	/**
	 * Renders the image for the owner of this behavior.
	 * @param string $version the name of the image version.
	 * @param string $alt the image alternative text
	 * @param array $htmlOptions the additional HTML options
	 */
	public function renderImage($version,$alt='',$htmlOptions=array())
	{
		if ($this->owner->hasAttribute('imageId'))
		{
			$image = Yii::app()->image->loadModel($this->owner->imageId);

			if ($image !== null)
				$image->render($version,$alt,$htmlOptions);
		}
	}
}
