const nextButtonSelector = '#controls a:nth-child(5)';
const isFirstPage = (path, startingPath) => path === startingPath;
const isPageWeHaventSeen = (path, knownPaths) => !knownPaths.includes(path);
const addPathToKnownPaths = (path, knownPaths) => {
	if (knownPaths.includes(path)) {
		return;
	}

	knownPaths.push(path);
	const json = JSON.stringify(knownPaths);
	cy.writeFile('cypress/fixtures/knownPaths.json', json);
};

const notify = path => cy.request(
	'post',
	Cypress.env('DISCORD_URL'),
	{ content: `Space Invader Hit: ${path}`, },
);

const checkNextPage = (knownPaths, startingPath) => {
	cy.get(nextButtonSelector)
		.click()
		.then(() => {
			cy.location().then(loc => {
				const path = loc.href;

				if (isFirstPage(path, startingPath)) {
					return;
				}

				if (isPageWeHaventSeen(path, knownPaths)) {
					addPathToKnownPaths(path, knownPaths);
					notify(path);
				}

				checkNextPage(knownPaths, startingPath);
			});
		});
};

describe('crawler', () => {
	it('can check for new products by clicking on next product', () => {
		cy.readFile('cypress/fixtures/knownPaths.json').then(knownPaths => {
			const startingPath = knownPaths[0];
			cy.visit(startingPath).then(page => checkNextPage(knownPaths, startingPath));
		});

	});
});

