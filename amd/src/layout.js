// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    local_urise
 * @author     Bernhard Fischer
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Modal form to manage booking option tags (botags).
 *
 * @module     local_urise
 * @copyright  2024 Wunderbyte GmbH
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


 /**
  * [Description for init]
  *
  * @return [type]
  *
  */
export function init() {
    // Run on initial load
    adjustHeights();

    // Re-run on window resize
    window.addEventListener('resize', adjustHeights);
}
 /**
  * Adjust height function for Wunderbyte Table.
  *
  * @return [type]
  *
  */
function adjustHeights() {

    if (window.innerWidth > 768) {
        const cards = document.querySelectorAll('.mod-booking-card');
        // Assuming you have a container for the cards.
        const container = document.querySelector('.wunderbyte-table-grid.rows-container');

        if (!cards || !container || !cards[0]) {
            return;
        }

        const cardWidth = cards[0].offsetWidth;
        const containerWidth = container.offsetWidth;

        // Calculate the number of cards per row
        const numberOfCardsInRow = Math.floor(containerWidth / cardWidth);

        if (numberOfCardsInRow < 1) {
            return;
        }

        // Reset heights
        cards.forEach(function(card) {
            const cardBody = card.querySelector('.mod-booking-card-body');
            const cardInfo = card.querySelector('.mod-booking-card-infos');
            const cardFooter = card.querySelector('.mod-booking-card-footer');

            cardBody.style.height = 'auto';
            cardInfo.style.height = 'auto';
            cardFooter.style.height = 'auto';
        });

        for (let i = 0; i < cards.length; i += numberOfCardsInRow) {
            let maxBodyHeight = 0;
            let maxInfoHeight = 0;
            let maxFooterHeight = 0;

            // Calculate the maximum heights for the current group of cards
            for (let j = i; j < i + numberOfCardsInRow && j < cards.length; j++) {
                const cardBody = cards[j].querySelector('.mod-booking-card-body');
                const cardInfo = cards[j].querySelector('.mod-booking-card-infos');
                const cardFooter = cards[j].querySelector('.mod-booking-card-footer');

                if (cardBody.offsetHeight > maxBodyHeight) {
                    maxBodyHeight = cardBody.offsetHeight;
                }
                if (cardInfo.offsetHeight > maxInfoHeight) {
                    maxInfoHeight = cardInfo.offsetHeight;
                }
                if (cardFooter.offsetHeight > maxFooterHeight) {
                    maxFooterHeight = cardFooter.offsetHeight;
                }
            }

            // Apply the maximum heights to the current group of cards
            for (let j = i; j < i + numberOfCardsInRow && j < cards.length; j++) {
                const cardBody = cards[j].querySelector('.mod-booking-card-body');
                const cardInfo = cards[j].querySelector('.mod-booking-card-infos');
                const cardFooter = cards[j].querySelector('.mod-booking-card-footer');

                cardBody.style.height = maxBodyHeight + 'px';
                cardInfo.style.height = maxInfoHeight + 'px';
                cardFooter.style.height = maxFooterHeight + 'px';
            }
        }
    }
}