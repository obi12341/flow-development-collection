<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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
 * Adds the aspect of persistence to repositories
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class DirtyMonitoring {

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @pointcut F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject && !within(F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface)
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function needsDirtyCheckingAspect() {}

	/**
	 * @introduce F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface, F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect
	 */
	public $dirtyMonitoringInterface;

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * After returning advice, making sure we have an UUID for each and every entity.
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @afterreturning classTaggedWith(entity) && method(.*->__construct())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function generateUUID(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$proxy->FLOW3_Persistence_Entity_UUID = \F3\FLOW3\Utility\Algorithms::generateUUID();
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isNew() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isNew())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return (!property_exists($proxy, 'FLOW3_Persistence_cleanProperties') || property_exists($proxy, 'FLOW3_Persistence_clone'));
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isClone() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean if the object is a clone
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isClone())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClone(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return property_exists($proxy, 'FLOW3_Persistence_clone');
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isDirty() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isDirty())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();

		if (property_exists($proxy, 'FLOW3_Persistence_cleanProperties')) {
			$isDirty = FALSE;
			$uuidPropertyName = $this->reflectionService->getClassSchema($joinPoint->getClassName())->getUUIDPropertyName();
			if ($uuidPropertyName !== NULL && !property_exists($proxy, 'FLOW3_Persistence_clone') && $proxy->FLOW3_AOP_Proxy_getProperty($uuidPropertyName) !== $proxy->FLOW3_Persistence_cleanProperties[$uuidPropertyName]) {
				throw new \F3\FLOW3\Persistence\Exception\TooDirty('My property "' . $uuidPropertyName . '" tagged as @uuid has been modified, that is simply too much.', 1222871239);
			}

			if (is_object($proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')])) {
				if ($proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')] != $proxy->FLOW3_AOP_Proxy_getProperty($joinPoint->getMethodArgument('propertyName'))) {
					$isDirty = TRUE;
				}
			} else {
				if ($proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')] !== $proxy->FLOW3_AOP_Proxy_getProperty($joinPoint->getMethodArgument('propertyName'))) {
					$isDirty = TRUE;
				}
			}
		} else {
			$isDirty = TRUE;
		}

		return $isDirty;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the FLOW3 persistence layer
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @before F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_memorizeCleanState())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();

		if ($joinPoint->getMethodArgument('propertyName') !== NULL) {
			$propertyNames = array($joinPoint->getMethodArgument('propertyName'));
		} else {
			$propertyNames = array_keys($this->reflectionService->getClassSchema($joinPoint->getClassName())->getProperties());
		}

		foreach ($propertyNames as $propertyName) {
			if (is_object($proxy->FLOW3_AOP_Proxy_getProperty($propertyName))) {
				$proxy->FLOW3_Persistence_cleanProperties[$propertyName] = clone $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
			} else {
				$proxy->FLOW3_Persistence_cleanProperties[$propertyName] = $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
			}
		}
	}

	/**
	 * Mark object as cloned after cloning.
	 *
	 * Note: this is done even if an object explicitly implements the
	 * DirtyMonitoringInterface to make sure it is proxied by the AOP
	 * framework (we need that to happen)
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @afterreturning method(.*->__clone())
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObject(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$proxy->FLOW3_Persistence_clone = TRUE;
	}
}
?>