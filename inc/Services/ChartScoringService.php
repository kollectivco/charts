<?php

namespace Charts\Services;

/**
 * Computes per-entry scoring using chart definition weights.
 */
class ChartScoringService {

	/**
	 * Default scoring weights.
	 */
	public function get_default_weights() {
		return array(
			'soundcharts_rank_weight' => 0.65,
			'popularity_weight'       => 0.15,
			'trend_weight'            => 0.10,
			'engagement_weight'       => 0.10,
			'decay_factor'            => 0.02,
			'momentum_weight'         => 1.5,
			'movement_weight'         => 1.0,
			'freshness_weight'        => 4.0,
		);
	}

	/**
	 * Resolve weights from a chart definition settings payload.
	 */
	public function get_weights_for_definition( $definition ) {
		$weights  = $this->get_default_weights();
		$settings = array();

		if ( $definition && ! empty( $definition->display_settings_json ) ) {
			$settings = json_decode( $definition->display_settings_json, true );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
		}

		foreach ( $weights as $key => $default ) {
			if ( isset( $settings[ $key ] ) && is_numeric( $settings[ $key ] ) ) {
				$weights[ $key ] = (float) $settings[ $key ];
			}
		}

		return $weights;
	}

	/**
	 * Calculate a final score and keep component traceability.
	 */
	public function score_row( array $normalized_row, $definition = null ) {
		$weights = $this->get_weights_for_definition( $definition );
		$rank    = max( 1, (int) ( $normalized_row['rank_position'] ?? 1 ) );

		// Explicit inverse-rank baseline so rank-only endpoints still produce a score.
		$rank_score = max( 0, 101 - min( 100, $rank ) );
		$components = array(
			'rank_inverse' => $rank_score,
			'popularity'   => (float) ( $normalized_row['popularity'] ?? 0 ),
			'trend'        => (float) ( $normalized_row['trend'] ?? 0 ),
			'engagement'   => (float) ( $normalized_row['engagement'] ?? 0 ),
		);

		$final_score = 0;
		$final_score += $components['rank_inverse'] * $weights['soundcharts_rank_weight'];
		$final_score += $components['popularity'] * $weights['popularity_weight'];
		$final_score += $components['trend'] * $weights['trend_weight'];
		$final_score += $components['engagement'] * $weights['engagement_weight'];

		$movement      = (int) ( $normalized_row['movement'] ?? 0 );
		$weeks_on_chart = max( 1, (int) ( $normalized_row['weeks_on_chart'] ?? 1 ) );
		$is_new        = ! empty( $normalized_row['is_new_entry'] );
		$momentum_boost = max( 0, $movement ) * $weights['momentum_weight'];
		$trend_boost    = $movement * $weights['movement_weight'];
		$freshness      = $is_new ? $weights['freshness_weight'] : max( 0, $weights['freshness_weight'] - ( $weeks_on_chart * $weights['decay_factor'] ) );
		$decay_penalty  = $weeks_on_chart * $weights['decay_factor'];
		$final_score   += $momentum_boost + $trend_boost + $freshness - $decay_penalty;

		return array(
			'final_score' => round( $final_score, 2 ),
			'weights'     => $weights,
			'components'  => $components,
			'derived'     => array(
				'rank_score_is_derived' => ! isset( $normalized_row['score'] ) && empty( $normalized_row['popularity'] ) && empty( $normalized_row['trend'] ) && empty( $normalized_row['engagement'] ),
				'momentum_boost'       => round( $momentum_boost, 2 ),
				'trend_boost'          => round( $trend_boost, 2 ),
				'freshness'            => round( $freshness, 2 ),
				'decay_penalty'        => round( $decay_penalty, 2 ),
			),
		);
	}
}
