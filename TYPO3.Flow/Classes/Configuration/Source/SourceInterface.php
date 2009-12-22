<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration\Source;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a configuration source
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content in a
	 * configuration container
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load, excluding the dot and file extension
	 * @return \F3\FLOW3\Configuration\Container
	 * @throws \F3\FLOW3\Configuration\Exception\NoSuchFile if the specified file does not exist
	 */
	public function load($pathAndFilename);

	/**
	 * Save the specified configuration container to the given file
	 *
	 * @param string $pathAndFilename Full path and file name of the file to write to, excluding the dot and file extension
	 * @param array $configuration The configuration array to save
	 * @return void
	 */
	public function save($pathAndFilename, array $configuration);

}
?>