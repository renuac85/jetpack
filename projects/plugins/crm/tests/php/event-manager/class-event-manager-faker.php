<?php

namespace Automattic\Jetpack\CRM\Event_Manager\Tests;

class Event_Manager_Faker {

	private static $instance;

	public static function instance(): Event_Manager_Faker {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function contact_data() {
		return array(
			'id'          => 1,
			'status'      => 'Lead',
			'name'        => 'John Doe',
			'email'       => 'johndoe@example.com',
			'lastupdated' => 1690385165,
		);
	}

	/**
	 * Return data for a dummy invoice.
	 *
	 * @param bool $get_as_data_type If true, return the data as a Data_Type_Invoice object.
	 * @return array|Data_Type_Invoice
	 */
	public function invoice_data() {
		$data = array(
			'id'   => 1,
			'data' => array(
				'id_override' => '1',
				'parent'      => '',
				'status'      => 'Unpaid',
				'due_date'    => 1690840800,
				'hash'        => 'ISSQndSUjlhJ8feWj2v',
				'lineitems'   => array(
					array(
						'net'      => 3.75,
						'desc'     => 'Dummy product',
						'quantity' => '3',
						'price'    => '1.25',
						'total'    => 3.75,
					),
				),
				'contacts'    => array( 1 ),
				'created'     => -1,
			),
		);

		return $data;
	}
}
