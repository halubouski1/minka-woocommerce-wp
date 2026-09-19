<?php
/**
 * Приводит существующие формы Gravity Forms в соответствие с
 * minka_form_definitions().
 *
 * Нужен потому, что minka_create_form() создаёт форму только один раз и
 * изменения определений на уже созданную форму не переносит.
 *
 * Сопоставление идёт по adminLabel: у сохранившихся полей id не меняется,
 * поэтому существующие записи не рассыпаются. Новые поля дописываются,
 * исчезнувшие из определения — удаляются.
 *
 * Запускать через scripts/sync-gf-forms.sh
 */

require '/var/www/html/wp-load.php';

if ( ! class_exists( 'GFAPI' ) || ! function_exists( 'minka_form_definitions' ) ) {
	fwrite( STDERR, "Gravity Forms или тема MINKA недоступны.\n" );
	exit( 1 );
}

$dry = in_array( '--dry-run', $argv, true );

foreach ( minka_form_definitions() as $key => $definition ) {
	$form_id = minka_form_id( $key );
	$form    = $form_id ? GFAPI::get_form( $form_id ) : null;

	if ( ! $form ) {
		echo "$key: формы нет, будет создана при первом обращении к сайту\n";
		continue;
	}

	echo "$key (форма $form_id)\n";

	// Существующие поля по машинному имени.
	$existing = array();
	$max_id   = 0;

	foreach ( $form['fields'] as $field ) {
		$name = isset( $field->adminLabel ) ? $field->adminLabel : '';

		if ( '' !== $name ) {
			$existing[ $name ] = $field;
		}

		$max_id = max( $max_id, (int) $field->id );
	}

	$fields  = array();
	$added   = array();
	$kept    = array();

	foreach ( $definition['fields'] as $name => $spec ) {
		if ( isset( $existing[ $name ] ) ) {
			// Поле остаётся — сохраняем его id, обновляем подпись и обязательность.
			$field             = $existing[ $name ];
			$field->label      = $spec['label'];
			$field->isRequired = ! empty( $spec['required'] );

			$fields[] = $field;
			$kept[]   = $name;
			continue;
		}

		++$max_id;

		$entry = array(
			'id'         => $max_id,
			'type'       => $spec['type'],
			'label'      => $spec['label'],
			'isRequired' => ! empty( $spec['required'] ),
			'adminLabel' => $name,
		);

		if ( ! empty( $spec['choices'] ) ) {
			$entry['choices'] = array();
			$entry['inputs']  = array();

			foreach ( $spec['choices'] as $index => $choice ) {
				$entry['choices'][] = array( 'text' => $choice, 'value' => $choice );

				if ( 'checkbox' === $spec['type'] ) {
					$entry['inputs'][] = array(
						'id'    => $max_id . '.' . ( $index + 1 ),
						'label' => $choice,
					);
				}
			}

			if ( 'checkbox' !== $spec['type'] ) {
				unset( $entry['inputs'] );
			}
		}

		$fields[] = GF_Fields::create( $entry );
		$added[]  = $name . ' (id ' . $max_id . ')';
	}

	$removed = array_diff( array_keys( $existing ), array_keys( $definition['fields'] ) );

	foreach ( $added as $a ) {
		echo "  + $a\n";
	}
	foreach ( $removed as $r ) {
		echo "  − $r (id " . $existing[ $r ]->id . ")\n";
	}
	if ( ! $added && ! $removed ) {
		echo "  без изменений\n";
		continue;
	}

	if ( $dry ) {
		echo "  (пробный запуск, не сохраняю)\n";
		continue;
	}

	$form['fields'] = $fields;
	$result         = GFAPI::update_form( $form );

	echo is_wp_error( $result )
		? '  ОШИБКА: ' . $result->get_error_message() . "\n"
		: "  сохранено\n";
}
