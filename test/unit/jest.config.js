module.exports = {
	testEnvironment: 'jsdom',
	rootDir: '../../',
	testMatch: [ '<rootDir>/src/test/**/*.js' ],
	moduleFileExtensions: [ 'js' ],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1',
	},
	setupFiles: [ '<rootDir>/test/unit/setup.js' ],
};
