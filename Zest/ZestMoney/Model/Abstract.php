<?php

namespace Zest\ZestMoney\Model;

abstract class AbstractClass {
	/**
	 * @return helper
	 */
	protected function _gethelper() {
		return $this->_objectManager->create('Zest\ZestMoney\Helper\Data');
	}

	/**
	 * @param  string
	 * @return array
	 */
	protected function _getheader($type = 'sensitive') {
		if ($type != 'sensitive') {
			$tokendata = $this->gettoken($type);
		} else {
			$tokendata = $this->gettoken();
		}

		$response = json_decode($tokendata);
		$header = array();
		$header[] = 'Authorization: ' . $response->token_type . ' ' . $response->access_token;
		return $header;
	}

	/**
	 * @return array
	 */
	protected function _getRefundReasonCode() {
		return array('DamagedGoods', 'GoodsNotReceived', 'NotAsDescribed', 'GOGW', 'PricingChanged', 'BuyerRemorse');
	}

	/**
	 * @return array
	 */
	public function _getCancellingreason() {
		return array('Damaged', 'NotReceived', 'NotAsDescribed', 'GestureOfGoodWill', 'PricingChanged', 'BuyerRemorse');
	}

	/**
	 * @return array
	 */
	protected function _getZestCancelStatus() {
		return array('Declined', 'CancelledTimeout', 'Cancelled', 'TimeoutCancelled');
	}

	/**
	 * @param  string
	 * @return boolean
	 */
	protected function _isZestRefundCode($commenttext) {
		return in_array($commenttext, $this->_getRefundReasonCode());
	}

}