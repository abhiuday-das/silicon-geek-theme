<?php
/**
 * Silicon Geek — Embedded + VLSI Starter Learning Paths (Step 7).
 *
 * NEW FILE:
 * wp-content/themes/ts-block-theme/inc/custom-post-type/ece-learning-paths.php
 *
 * Load it from functions.php after ece-project-render.php:
 * require_once get_template_directory() . '/inc/custom-post-type/ece-learning-paths.php';
 *
 * What this file does:
 *  - Uses the existing hierarchical Course Structure taxonomy (ece_course).
 *  - One time, creates starter Modules and Topics below these roots:
 *      Embedded Systems → Module → Topic
 *      VLSI Design      → Module → Topic
 *  - Does NOT create a new post type, taxonomy, template or menu system.
 *  - Existing [sg_course_index] pages automatically list the new folders.
 *  - Existing [sg_note_page] layout automatically renders lessons assigned
 *    to the deepest Topic terms.
 *
 * Authoring rule:
 *  - Create a normal ECE Note.
 *  - Assign it only to the deepest Topic (for example: UART).
 *  - Use Lesson Order 10, 20, 30... when a Topic has multiple notes.
 *
 * The seed runs only once. After it has run, folders can be renamed,
 * reordered, added or deleted normally from WP Admin → Course Structure.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TS_SG_LEARNING_PATHS_SEED_OPTION' ) ) {
	define( 'TS_SG_LEARNING_PATHS_SEED_OPTION', 'ts_sg_learning_paths_seed_v1' );
}

add_action( 'admin_init', 'ts_sg_learning_paths_seed_starter_tree', 30 );

/**
 * Create the Embedded and VLSI starter trees one time.
 *
 * Step 1's starter seed runs at admin_init priority 20, so priority 30
 * also works on a fresh install where the two root tracks do not exist yet.
 */
function ts_sg_learning_paths_seed_starter_tree() {
	if ( get_option( TS_SG_LEARNING_PATHS_SEED_OPTION ) ) {
		return;
	}

	if (
		! defined( 'TS_CS_COURSE_TAX' ) ||
		! taxonomy_exists( TS_CS_COURSE_TAX ) ||
		! function_exists( 'ts_cs_course_seed_branch' )
	) {
		return;
	}

	ts_cs_course_seed_branch( ts_sg_learning_paths_tree(), 0 );

	/*
	 * Mark the seed complete only when both roots and one deep starter
	 * Topic from each path are present. This makes a partial/database
	 * failure retry on the next admin load instead of being lost forever.
	 */
	$required_slugs = array(
		'embedded-systems',
		'embedded-uart',
		'vlsi-design',
		'vlsi-verilog-getting-started',
	);

	foreach ( $required_slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, TS_CS_COURSE_TAX );

		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
	}

	update_option( TS_SG_LEARNING_PATHS_SEED_OPTION, 1 );
}

/**
 * Convert compact Topic tuples into the node format used by the
 * existing Step 1 seeder. Orders are assigned as 10, 20, 30...
 *
 * @param array $topics Each item: [ name, slug, description ].
 * @return array
 */
function ts_sg_learning_paths_topic_nodes( $topics ) {
	$nodes = array();
	$order = 10;

	foreach ( (array) $topics as $topic ) {
		$nodes[] = array(
			'name'        => $topic[0],
			'slug'        => $topic[1],
			'order'       => $order,
			'description' => $topic[2],
			'children'    => array(),
		);

		$order += 10;
	}

	return $nodes;
}

/**
 * Build one Module node with its ordered starter Topics.
 *
 * @param string $name        Module name.
 * @param string $slug        Unique Course Structure slug.
 * @param int    $order       Display Order.
 * @param string $description Short card/listing description.
 * @param array  $topics      Compact Topic tuples.
 * @return array
 */
function ts_sg_learning_paths_module_node( $name, $slug, $order, $description, $topics ) {
	return array(
		'name'        => $name,
		'slug'        => $slug,
		'order'       => (int) $order,
		'description' => $description,
		'children'    => ts_sg_learning_paths_topic_nodes( $topics ),
	);
}

/**
 * Starter curriculum for Step 7.
 *
 * These are folders only, not dummy notes. Real notes are added later
 * as normal ECE Note posts assigned to one deepest Topic each.
 *
 * @return array
 */
function ts_sg_learning_paths_tree() {
	return array(
		array(
			'name'        => 'Embedded Systems',
			'slug'        => 'embedded-systems',
			'order'       => 30,
			'description' => 'Microcontrollers, Embedded C, peripherals, protocols, RTOS and IoT learning path.',
			'children'    => array(
				ts_sg_learning_paths_module_node(
					'Embedded C Foundations',
					'embedded-c-foundations',
					10,
					'C programming concepts that are directly useful in microcontroller firmware.',
					array(
						array( 'C Basics for Embedded', 'embedded-c-basics', 'Variables, loops, conditions and the structure of a small firmware program.' ),
						array( 'Data Types & Memory', 'embedded-data-types-memory', 'Fixed-width data types, signedness, storage classes and memory basics.' ),
						array( 'Bitwise Operations', 'embedded-bitwise-operations', 'Setting, clearing, toggling and testing register bits safely.' ),
						array( 'Pointers & Registers', 'embedded-pointers-registers', 'Pointers, volatile, memory-mapped registers and common pointer mistakes.' ),
						array( 'Functions, Files & Headers', 'embedded-functions-headers', 'Modular firmware structure with functions, source files and header files.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'GPIO & Core Peripherals',
					'embedded-gpio-peripherals',
					20,
					'The essential on-chip peripherals used in almost every embedded project.',
					array(
						array( 'GPIO Basics', 'embedded-gpio-basics', 'Input, output, pull-up/pull-down and reading switches or controlling LEDs.' ),
						array( 'Timers & Counters', 'embedded-timers-counters', 'Timer modes, delays, counting, prescalers and overflow concepts.' ),
						array( 'Interrupts', 'embedded-interrupts', 'Interrupt flow, ISR rules, flags, priorities and debouncing basics.' ),
						array( 'ADC & DAC', 'embedded-adc-dac', 'Analog sampling, resolution, reference voltage and digital-to-analog output.' ),
						array( 'PWM Basics', 'embedded-pwm-basics', 'Duty cycle, frequency and PWM for LEDs, buzzers and motors.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'Communication Protocols',
					'embedded-communication-protocols',
					30,
					'Serial interfaces used to connect microcontrollers, sensors and modules.',
					array(
						array( 'UART', 'embedded-uart', 'Baud rate, frame format, TX/RX wiring and serial debugging.' ),
						array( 'SPI', 'embedded-spi', 'MOSI, MISO, clock, chip select, modes and full-duplex transfers.' ),
						array( 'I2C', 'embedded-i2c', 'SDA, SCL, addressing, start/stop conditions and pull-up resistors.' ),
						array( 'CAN Basics', 'embedded-can-basics', 'CAN bus concepts, frames, arbitration and automotive use cases.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'Microcontroller Platforms',
					'embedded-microcontroller-platforms',
					40,
					'Popular controller families and development boards used by students and industry.',
					array(
						array( '8051 Basics', 'embedded-8051-basics', '8051 architecture, ports, timers and simple assembly/C programs.' ),
						array( 'AVR & Arduino', 'embedded-avr-arduino', 'AVR registers, Arduino workflow and moving from functions to registers.' ),
						array( 'ARM Cortex-M Basics', 'embedded-arm-cortex-m', 'Cortex-M architecture, memory map, NVIC and bare-metal basics.' ),
						array( 'ESP32 Getting Started', 'embedded-esp32-getting-started', 'ESP32 setup, GPIO, Wi-Fi/Bluetooth overview and first IoT program.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'RTOS & System Design',
					'embedded-rtos-system-design',
					50,
					'Real-time concepts and practical habits for reliable embedded products.',
					array(
						array( 'RTOS Concepts', 'embedded-rtos-concepts', 'Why RTOS is used, kernels, tick, deadlines and bare-metal comparison.' ),
						array( 'Tasks & Scheduling', 'embedded-rtos-tasks-scheduling', 'Task states, priorities, delays and basic scheduling behaviour.' ),
						array( 'Semaphores, Mutex & Queues', 'embedded-rtos-sync-queues', 'Task communication and synchronization primitives with examples.' ),
						array( 'Debugging & Testing', 'embedded-debugging-testing', 'Serial logs, breakpoints, logic testing and common firmware faults.' ),
					)
				),
			),
		),
		array(
			'name'        => 'VLSI Design',
			'slug'        => 'vlsi-design',
			'order'       => 40,
			'description' => 'Digital design, CMOS, Verilog, RTL, verification, timing and FPGA learning path.',
			'children'    => array(
				ts_sg_learning_paths_module_node(
					'Digital Design Foundations',
					'vlsi-digital-design-foundations',
					10,
					'The digital logic base required before HDL and chip-design topics.',
					array(
						array( 'Number Systems & Codes', 'vlsi-number-systems-codes', 'Binary, octal, hexadecimal, signed numbers and common digital codes.' ),
						array( 'Boolean Algebra & K-Map', 'vlsi-boolean-algebra-kmap', 'Boolean simplification, SOP/POS forms and Karnaugh maps.' ),
						array( 'Combinational Circuits', 'vlsi-combinational-circuits', 'Adders, multiplexers, decoders, encoders and comparators.' ),
						array( 'Sequential Circuits', 'vlsi-sequential-circuits', 'Latches, flip-flops, registers, counters and shift registers.' ),
						array( 'FSM Design', 'vlsi-fsm-design', 'Mealy/Moore machines, state diagrams, encoding and reset behaviour.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'CMOS Fundamentals',
					'vlsi-cmos-fundamentals',
					20,
					'How MOS transistors and CMOS gates actually implement digital logic.',
					array(
						array( 'MOS Transistor Basics', 'vlsi-mos-transistor-basics', 'NMOS/PMOS operation, threshold voltage and operating regions.' ),
						array( 'CMOS Inverter', 'vlsi-cmos-inverter', 'Inverter circuit, transfer curve, noise margin and switching behaviour.' ),
						array( 'CMOS Logic Gates', 'vlsi-cmos-logic-gates', 'Pull-up/pull-down networks for NAND, NOR and complex gates.' ),
						array( 'Delay, Power & Scaling', 'vlsi-delay-power-scaling', 'Propagation delay, dynamic/static power and technology scaling basics.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'Verilog HDL',
					'vlsi-verilog-hdl',
					30,
					'Write synthesizable digital hardware and simple testbenches in Verilog.',
					array(
						array( 'Verilog Getting Started', 'vlsi-verilog-getting-started', 'Modules, ports, assignments and the difference between hardware and software.' ),
						array( 'Data Types & Operators', 'vlsi-verilog-data-types-operators', 'wire, reg/logic, vectors, operators and number literals.' ),
						array( 'Combinational Verilog', 'vlsi-verilog-combinational', 'assign, always blocks, case/if and avoiding unintended latches.' ),
						array( 'Sequential Verilog', 'vlsi-verilog-sequential', 'Clocked always blocks, reset styles, registers and counters.' ),
						array( 'Testbench Basics', 'vlsi-verilog-testbench', 'Clock generation, stimulus, monitoring and waveform checking.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'RTL Design & Verification',
					'vlsi-rtl-design-verification',
					40,
					'Good RTL habits and an introduction to functional verification.',
					array(
						array( 'RTL Coding Guidelines', 'vlsi-rtl-coding-guidelines', 'Blocking/non-blocking assignments, resets, FSM style and synthesizable code.' ),
						array( 'Simulation & Debugging', 'vlsi-simulation-debugging', 'Waveforms, logs, assertions in testbenches and debugging mismatches.' ),
						array( 'Assertions Introduction', 'vlsi-assertions-introduction', 'Immediate/concurrent assertions and protocol-checking ideas.' ),
						array( 'UVM Overview', 'vlsi-uvm-overview', 'What UVM is, common components and where it fits in verification.' ),
					)
				),
				ts_sg_learning_paths_module_node(
					'Synthesis, Timing & FPGA',
					'vlsi-synthesis-timing-fpga',
					50,
					'What happens after RTL: synthesis, timing closure and FPGA implementation.',
					array(
						array( 'ASIC vs FPGA', 'vlsi-asic-vs-fpga', 'Differences in cost, speed, flexibility and design flow.' ),
						array( 'Logic Synthesis Basics', 'vlsi-logic-synthesis-basics', 'RTL to gate-level netlist, libraries, area and optimization goals.' ),
						array( 'Static Timing Basics', 'vlsi-static-timing-basics', 'Setup, hold, clock period, slack and critical paths.' ),
						array( 'FPGA Design Flow', 'vlsi-fpga-design-flow', 'Synthesis, implementation, constraints, bitstream and board testing.' ),
					)
				),
			),
		),
	);
}
