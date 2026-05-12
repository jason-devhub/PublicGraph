import { startStimulusApp } from '@symfony/stimulus-bundle';
import MiniGraphController from './controllers/mini-graph_controller.js';
import GlobalGraphController from './controllers/global_graph_controller.js';

const app = startStimulusApp();
app.register('mini-graph', MiniGraphController);
app.register('global-graph', GlobalGraphController);
