<?php if ( ! defined('CARTTHROB_PATH')) Cartthrob_core::core_error('No direct script access allowed');

class Cartthrob_discount_buy_abcdef_get_x_off_ghi extends Cartthrob_discount
{
	public $title = 'Buy any of a-b-c-d-e-f, get discount off g-h-i';
	public $settings = array(
		array(
			'name' => 'discount_quantity',
			'short_name' => 'get_x_free',
			'note' => 'enter_the_number_of_items',
			'type' => 'text'
		),
		array(
			'name' => 'percentage_off',
			'short_name' => 'percentage_off',
			'note' => 'enter_the_percentage_discount',
			'type' => 'text'
		),
		array(
			'name' => 'amount_off',
			'short_name' => 'amount_off',
			'note' => 'enter_the_discount_amount',
			'type' => 'text'
		),
		array(
			'name' => 'qualifying_entry_ids',
			'short_name' => 'entry_ids',
			'note' => 'Separate multiple entry_ids by comma (ie. a,b,c,d,e,f)',
			'type' => 'text'
		),
		array(
			'name' => 'Discounted entry_id',
			'short_name' => 'discounted_entry_ids',
			'note' => 'Separate multiple entry_ids by comma (ie. g,h,i)',
			'type' => 'text'
		),
		array(
			'name' => 'per_item_limit',
			'short_name' => 'item_limit',
			'note' => 'per_item_limit_note',
			'type' => 'text'
		),
	);
	
	function get_discount()
	{
		
		$discount 			= 0;
		$entry_ids 			= array();
		$discounted_entry_ids	= array();
		
		// CHECK AMOUNTS AND PERCENTAGES
		if ($this->plugin_settings('percentage_off') !== '')
		{
			$percentage_off = ".01" * $this->core->sanitize_number( $this->plugin_settings('percentage_off') );

			if ($percentage_off > 100)
			{
				$percentage_off = 100; 
			}
			else if ($percentage_off < 0)
			{
				$percentage_off = 0; 
			}
		}
		else
		{
			$amount_off = $this->core->sanitize_number( $this->plugin_settings('amount_off') );
		}
		
		// CHECK ENTRY IDS
		if ( $this->plugin_settings('entry_ids') )
		{
			$entry_ids = preg_split('/\s*[|,-]\s*/', trim( $this->plugin_settings('entry_ids') ));
		}
		
		if ( $this->plugin_settings('discounted_entry_ids') )
		{
			$discounted_entry_ids = preg_split('/\s*[|,-]\s*/', trim( $this->plugin_settings('discounted_entry_ids') ));
		}
		
		if ( ! $entry_ids)
		{
			return 0;
		}
		
		//doesn't have one of the qualifying products
		if ( ! array_intersect($entry_ids, $this->core->cart->product_ids()))
		{
			return 0;
		}
		
		//doesn't have one of the qualifying products
		if ( ! array_intersect($discounted_entry_ids, $this->core->cart->product_ids()))
		{
			return 0;
		}
		
		$item_limit = ( $this->plugin_settings('item_limit') ) ? $this->plugin_settings('item_limit') : FALSE;
			
		$items = array();

		foreach ($this->core->cart->items() as $item)
		{
			if ( $item->product_id() && in_array( $item->product_id(), $discounted_entry_ids))
			{
				for ($i=0; $i<$item->quantity() ;$i++)
				{
					$items[] = $item->price(); 
				}
			}
		}
			
		// sort the items so the lowest prices are last
		rsort($items);
		
 		$counts = array();
		reset($items);			

		while (($price = current($items)) !== FALSE)
		{
			$key = key($items);

			$count = count($items);
			while($count > 0 )
			{
				if ($item_limit !== FALSE && $item_limit < 1)
				{
					next($items);
						continue 2;
				}

				if ($this->plugin_settings('get_x_free'))
				{
					$free_count = ($count > $this->plugin_settings('get_x_free')) ? $this->plugin_settings('get_x_free') : $count;
				}
				else
				{
					$free_count = $count; 
				}
				
				if (isset($percentage_off))
				{
					//get the lowest price by grabbing the last array item
					//since our array is sorted by price
					for ($i=0;$i<$free_count;$i++)
					{
						$discount += end($items) * $percentage_off;
						array_pop($items);
					}

					//go back to where we were
					reset($items);
					while ($key != key($items)) next($items);
				}
				else
				{
					for ($i=0;$i<$free_count;$i++)
					{
						array_pop($items);
						$discount += $amount_off;
					}
				}

				$count -= $free_count;

				if ($item_limit !== FALSE)
				{
					$item_limit--;
				}
			}

			next($items);
		}

		return $discount;
	}

	function validate()
	{
		if ( ! $this->plugin_settings('entry_ids'))
		{
			$this->set_error('No qualifying products');
			return FALSE;
		}
		
		if ( ! $this->plugin_settings('discounted_entry_ids'))
		{
			$this->set_error('No qualifying products');
			return FALSE;
		}
		
		$entry_ids = preg_split('/\s*[|,-]\s*/', trim($this->plugin_settings('entry_ids')));
		
		$discounted_entry_ids = preg_split('/\s*[|,-]\s*/', trim($this->plugin_settings('discounted_entry_ids')));
		
		if ( ! array_intersect($entry_ids, $this->core->cart->product_ids()) ||  ! array_intersect($discounted_entry_ids, $this->core->cart->product_ids()))
		{
			$this->set_error('No qualifying products');
			return FALSE;
		}
		
		return TRUE;
	}
	
}