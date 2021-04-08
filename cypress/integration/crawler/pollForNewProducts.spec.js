const nextButtonSelector = '#controls a:nth-child(5)';
const startingPage = 'https://space-invaders.com/spaceshop/product/21';
const knownPages = ['https://space-invaders.com/spaceshop/product/21', 'https://space-invaders.com/spaceshop/product/30'];

const isFirstPage = path => path === startingPage;
const isPageWeHaventSeen = path => !knownPages.includes(path);

const notify = path => cy.request(
	'post',
	'https://discord.com/api/webhooks/826626110129176677/oBHMyl6gsECWacYk6YEzpeLMHIpWbLN1ePH8vrvAKseNEQUg8jR5YTab2mgXOE7UmXlU',
	{ content: `Space Invader Hit: ${path}`, },
);

const checkNextPage = () => {
	cy.get(nextButtonSelector)
		.click()
		.then(() => {
			cy.location().then(loc => {
				const path = loc.href;

				if (isFirstPage(path)) {
					return;
				}

				if (isPageWeHaventSeen(path)) {
					notify(path);
					return;
				}

				checkNextPage();
			});
		});
};

describe('crawler', () => {
	it('can check for new products by clicking on next product', () => {
		cy.visit(startingPage).then(page => checkNextPage());
	});
});
