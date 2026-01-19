-- Add sample data into the database
-- Insert sample data into the Affiliation table
INSERT INTO Affiliation (AffiliationName)
VALUES
('New York University Shanghai'),
('Massachusetts Institute of Technology'),
('Stanford University'),
('Fudan University'),
('Columbia University'),
('University of Oxford'),
('Peking University'),
('Harvard University'),
('University of Cambridge'),
('University of Tokyo');

-- Insert sample data into the User table
INSERT INTO User (UserName, Email, Password, AffiliationID, Role)
VALUES
('jessieLi', 'jessieLi@nyu.edu.cn', '$2y$10$LJJf8AjS7sAxHr4RKqfpLOEnOU1poiK3688eYp3NTQm5vm3S.jiiS', 1, 'Student'),
('JamesWang', 'james.wang@mit.edu', '$2y$10$cqrb2ItLFoGiu3ATQQFi5.7cIEwbUv/s7CqrZB9NkJ1hubPGuJRwS', 2, 'Researcher'),
('AliceChen', 'alice.chen@stanford.edu', '$2y$10$dvVND8xW.okLE1p9r0In.u/RQnp5jgD469LsnmCbZoNq.kujcGcKW', 3, 'Professor'),
('RobertLee', 'robert.lee@nyu.edu', '$2y$10$SNWIOAOAXYqhG5mdaCFT0Oyu5Hn/2GdIikTDfzBGEx.yJbMwaIxXe', 1, 'Admin'),
('EmilyZhang', 'emily.zhang@fudan.edu.cn', '$2y$10$86gMIfT3yhhxuXB1sMKM.O0IUnppX6cEGCvoTOpFbDlLla35BGS2K', 4, 'Student'),
('DavidKim', 'david.kim@columbia.edu', '$2y$10$VcwWowDqyTycn7bMPo7MC.dRI1VtGjFFWpCKiqEfn/zReXkzCFf.6', 5, 'Researcher'),
('SophiaWu', 'sophia.wu@ox.ac.uk', '$2y$10$Y5O4eHJUbs8qjruTU20SeOQSWn.KdolUWS4rLNWD2PEcZuEXror3W', 6, 'Professor'),
('MichaelTan', 'michael.tan@ucl.ac.uk', '$2y$10$y75zamCDw4hJagCXoD/fd.6UbjaP77cixIgX2ZHgnngnW5fDGkKUK', 6, 'Student'),
('OliviaLiu', 'olivia.liu@pku.edu.cn', '$2y$10$RZKbWnNtTo5yzyFUJ8vWO.7E03qjEMY.3/fI/E/Pk7bmlrKhkVAq2', 7, 'Researcher'),
('EthanZhao', 'ethan.zhao@harvard.edu', '$2y$10$VrDyaSO3K0GrjOJjylCxV.rY2x.y2s8TqbT/s9avryg1OPw4Umn4S', 8, 'Admin');

-- Insert sample data into the Source table
INSERT INTO Source (SourceType, SourceName, Language)
VALUES
('Journal', 'Nature Neuroscience', 'English'),
('Journal', 'The Lancet Neurology', 'English'),
('Preprint Server', 'arXiv', 'English'),
('Conference Proceedings', 'Proceedings of the IEEE Conference on Computer Vision and Pattern Recognition (CVPR)', 'English'),
('Journal', 'PLOS ONE', 'English'),
('Conference Proceedings', 'Annual Meeting of the Cognitive Neuroscience Society (CNS)', 'English'),
('Preprint Server', 'bioRxiv', 'English'),
('Journal', 'Frontiers in Human Neuroscience', 'English'),
('Journal', 'Journal of Neuroscience', 'English'),
('Journal', 'Scientific Reports', 'English');

-- Insert sample data into the Author table
INSERT INTO Author (FirstName, LastName, AffiliationID, AuthorArea)
VALUES
( 'Shuo', 'Zhang', 1, 'Neuroscience / Decision-Making'),
( 'Anne G.E.', 'Collins', 2, 'Neuroscience / Computational Models'),
( 'Márton', 'Pándy', 3, 'Computer Science / Artificial Intelligence'),
( 'Giorgia', 'Mazzi', 4, 'Computational Neuroscience / Multisensory Integration'),
( 'Wei', 'Liu', 5, 'Neuroimaging / Machine Learning'),
( 'Stephen M.', 'Fleming', 6, 'Neuroscience / Metacognition'),
( 'Ehsan', 'Futuhi', 3, 'Computer Science / Search Algorithms'),
( 'Yongcheng', 'Zong', 7, 'Neuroimaging / Generative Models'),
( 'Xiaoyu', 'Li', 8, 'Ethics / Digital Health'),
( 'Shuqiang', 'Wang', 7, 'Neuroimaging / Generative Models'),
( 'Emily', 'Weiner', 8, 'Ethics / Digital Health');

-- Insert sample data into the Document table
INSERT INTO Document (Title, Abstract, PublicationYear, SourceID, Area, ISBN, LinkPath, ImportDate, ReviewStatus)
VALUES
('The Neural Correlates of Ambiguity and Risk in Human Decision-Making',
 'This study examines how the human brain represents different types of uncertainty—ambiguity and risk—during decision-making tasks, using behavioral and neuroimaging evidence.',
 2023, 1, 'Neuroscience / Decision-Making', NULL,
 'https://www.biorxiv.org/content/10.1101/2023.09.18.558250v2.full.pdf',
 '2025-10-29','Approved'),

('Frontal Lobe Function and Human Decision-Making',
 'This article reviews computational models and empirical evidence regarding the role of the prefrontal cortex in decision-making under uncertainty.',
 2012, 2, 'Neuroscience / Computational Models', NULL,
 'https://journals.plos.org/plosbiology/article?id=10.1371/journal.pbio.1001293',
 '2025-10-29','Approved'),

('Learning Graph Search Heuristics',
 'This paper proposes learning-based methods for automatically generating heuristics in graph search, improving the performance of A* and related algorithms.',
 2022, 3, 'Computer Science / Artificial Intelligence', NULL,
 'https://arxiv.org/abs/2212.03978',
 '2025-10-29','Approved'),

('Prior Expectations Guide Multisensory Integration During Face-to-Face Communication',
 'The study demonstrates that prior expectations shape how the human brain integrates auditory and visual cues during natural social interaction.',
 2025, 4, 'Computational Neuroscience / Multisensory Integration', NULL,
 'https://journals.plos.org/ploscompbiol/article?id=10.1371/journal.pcbi.1013468',
 '2025-10-29','Approved'),

('Graph Neural Networks for Brain Graph Learning: A Survey',
 'A comprehensive review of graph neural network (GNN) applications in brain network modeling and neuroimaging analysis.',
 2024, 5, 'Neuroimaging / Machine Learning', NULL,
 'https://arxiv.org/html/2406.02594v1',
 '2025-10-29','Approved'),

('Decision-Making Under an Active Inference Framework',
 'Using behavioral and neural data, this paper explains decision-making within the active inference framework, highlighting uncertainty and exploration-exploitation balance.',
 2025, 6, 'Cognitive Neuroscience / Theoretical Neuroscience', NULL,
 'https://elifesciences.org/articles/92892.pdf',
 '2025-10-29','Approved'),

('The Neural System of Metacognition Accompanying Decision Making',
 'This study identifies a distinct prefrontal system supporting metacognition during decision-making, using fMRI evidence.',
 2012, 2, 'Neuroscience / Metacognition', NULL,
 'https://journals.plos.org/plosbiology/article?id=10.1371/journal.pbio.2004037',
 '2025-10-29','Approved'),

('Learning Admissible Heuristics for A*: Theory and Practice',
 'This paper investigates the theory and empirical performance of machine learning methods for generating admissible heuristics in graph search.',
 2025, 3, 'Computer Science / Search Algorithms', NULL,
 'https://www.arxiv.org/pdf/2509.22626',
 '2025-10-29','Approved'),

('BrainNetDiff: Generative AI Empowers Brain Network Generation via Multimodal Diffusion Model',
 'A diffusion-based generative model for constructing brain networks from multimodal data, enhancing brain imaging analysis and disease prediction.',
 2023, 9, 'Neuroimaging / Generative Models', NULL,
 'https://arxiv.org/pdf/2311.05199',
 '2025-10-29','Approved'),

('Ethical Challenges and Evolving Strategies in the Integration of AI in Healthcare and Medical Education',
 'A scoping review of ethical challenges—privacy, fairness, and accountability—in integrating artificial intelligence into medical systems and education.',
 2025, 10, 'Ethics / Digital Health', NULL,
 'https://journals.plos.org/plosone/article/file?id=10.1371/journal.pone.0333411&type=printable',
 '2025-10-29','Approved');

-- Insert sample data into the Tag table
INSERT INTO Tag (TagName, TagDescription)
VALUES
('Neural Networks',
 'Computational models inspired by the human brain, used in deep learning and cognitive modeling.'),

('Functional MRI',
 'Neuroimaging technique measuring brain activity by detecting changes in blood oxygen levels.'),

('Cognitive Neuroscience',
 'The study of neural mechanisms underlying mental processes like perception, memory, and decision-making.'),

('Machine Learning',
 'Algorithms that enable computers to learn from and make predictions based on data.'),

('Brain Connectivity',
 'The study of how different regions of the brain communicate structurally and functionally.'),

('Visual Perception',
 'The process of interpreting and organizing visual information from the environment.'),

('Consciousness',
 'The state of awareness of oneself and the environment, central to philosophy and neuroscience.'),

('Deep Learning',
 'A subset of machine learning focusing on multi-layered neural networks for data representation.'),

('Neuroplasticity',
 'The brain\'s ability to reorganize itself by forming new neural connections throughout life.'),

('Computational Modeling',
 'The use of mathematical and algorithmic models to simulate biological or cognitive processes.');

-- Insert sample data into the Collection table

INSERT INTO Collection (UserID, CollectionName, CollectionDescription, CreatedTime, UpdatedTime, Visibility)
VALUES
(1, 'Neural Dynamics Papers',
 'A collection of papers focusing on brain network dynamics and computational models.',
 '2024-03-15 10:24:00', '2025-02-20 14:05:00', 'Public'),

(2, 'Vision and Perception Studies',
 'Selected readings on human visual perception and related fMRI studies.',
 '2024-06-02 09:15:00', '2025-01-18 11:40:00', 'Public'),

(3, 'AI and Deep Learning',
 'Research articles on deep learning architectures and neural representation.',
 '2023-11-25 13:45:00', '2025-03-10 16:30:00', 'Public'),

(4, 'Cognitive Control and Attention',
 'A curated set of papers exploring the neural basis of attention and executive functions.',
 '2024-01-12 08:55:00', '2025-02-14 19:22:00', 'Private'),

(5, 'Brain Connectivity Models',
 'Key publications on structural and functional brain connectivity modeling.',
 '2023-09-30 10:00:00', '2025-01-05 17:10:00', 'Public'),

(6, 'Social Neuroscience Insights',
 'Papers on empathy, social cognition, and neural correlates of emotion.',
 '2024-05-04 12:18:00', '2025-03-01 09:46:00', 'Public'),

(7, 'Consciousness and the Brain',
 'Research collections on consciousness theories, neural correlates, and philosophy of mind.',
 '2024-04-17 11:42:00', '2025-02-09 15:27:00', 'Public'),

(8, 'Computational Neuroscience Reviews',
 'Comprehensive resources for modeling neural computation and dynamics.',
 '2023-12-21 15:40:00', '2025-04-01 10:33:00', 'Public'),

(9, 'Cognitive Psychology and AI',
 'Articles bridging computational models and cognitive science approaches.',
 '2024-02-07 09:05:00', '2025-01-28 13:12:00', 'Public'),

(10, 'Neural Representation Theory',
 'A collection of works exploring how information is represented in neural systems.',
 '2024-03-10 16:25:00', '2025-03-29 11:55:00', 'Private');

-- Insert sample data into the Notes table
INSERT INTO Notes (DocumentID, UserID, Content, PageNum, CreatedTime, UpdatedTime, Visibility)
VALUES
(1, 1,
 'Friston\'s free-energy principle provides a unified framework for understanding neural inference. Need to explore its connection to predictive coding.',
 '12', '2024-05-11 10:35:00', '2025-02-03 15:10:00', 'Private'),

(2, 2,
 'fMRI results indicate that face-selective regions are more consistent across subjects than expected. This supports domain-specific representations.',
 '7', '2024-06-14 09:10:00', '2025-01-12 18:00:00', 'Public'),

(3, 3,
 'LeCun\'s 2015 paper marks the foundation of modern deep learning. CNN principles still dominate AI vision systems.',
 '3', '2023-11-29 11:45:00', '2025-03-05 16:40:00', 'Private'),

(4, 4,
 'Interesting connection between reinforcement learning and brain\'s reward prediction error signals. Could integrate with DTI data.',
 '18', '2024-01-18 08:55:00', '2025-02-14 09:30:00', 'Private'),

(5, 5,
 'Graph-theoretical measures of brain networks reveal modular organization similar to small-world systems.',
 '22', '2023-10-04 13:15:00', '2025-01-07 17:10:00', 'Public'),

(6, 6,
 'Gazzaniga argues for modular brain organization. Might relate to neural independence in split-brain studies.',
 '9', '2024-05-06 12:10:00', '2025-03-02 10:55:00', 'Private'),

(7, 7,
 'Singer\'s work on empathy emphasizes the insula\'s role. Worth comparing with mirror neuron system literature.',
 '15', '2024-04-20 14:35:00', '2025-02-11 11:50:00', 'Public'),
(8, 8,
 'Dehaene\'s global workspace theory provides strong empirical evidence for consciousness as distributed processing.',
 '5', '2023-12-23 15:00:00', '2025-04-03 09:25:00', 'Private'),

(9, 9,
 'Koch links integrated information theory with physical correlates of consciousness. Complex but stimulating.',
 '13', '2024-02-09 09:20:00', '2025-01-29 14:15:00', 'Private'),

(10, 10,
 'Sompolinsky\'s computational models explain how neural variability can still lead to reliable population coding.',
 '8', '2024-03-13 16:45:00', '2025-03-30 12:05:00', 'Public');

-- Insert sample data into the Citation table
INSERT INTO Citation (CitingDocumentID, CitedDocumentID, ContextPage, DetectedAt)
VALUES
(2, 1,
 'Building upon Friston\'s free-energy framework, this study explores predictive mechanisms in visual cortex responses.',
 '2025-01-10 14:05:00'),

(3, 1,
 'The hierarchical structure in deep networks may parallel the predictive coding models described by Friston (2010).',
 '2025-01-12 09:22:00'),

(4, 3,
 'Reinforcement learning architectures share representational similarities with convolutional neural networks proposed by LeCun et al. (2015).',
 '2025-02-02 18:40:00'),

(5, 1,
 'Brain network modularity may reflect the self-organizing principles discussed in Friston\'s computational brain models.',
 '2025-01-08 11:30:00'),

(6, 2,
 'Findings on hemispheric specialization align with Kanwisher\'s results on face-selective cortical regions.',
 '2025-02-17 16:45:00'),

(7, 6,
 'Singer\'s work expands upon Gazzaniga\'s theory of modular consciousness in social and emotional processing.',
 '2025-03-01 09:50:00'),

(8, 7,
 'The global workspace model integrates social-emotional components such as empathy, as discussed by Singer (2013).',
 '2025-03-04 13:22:00'),

(9, 8,
 'Dehaene\'s global workspace theory provides a theoretical foundation for Koch\'s analysis of conscious processing.',
 '2025-01-30 11:00:00'),

(10, 9,
 'Sompolinsky\'s computational models offer a mathematical formulation for neural integration as described by Koch (2014).',
 '2025-02-28 10:40:00'),

(5, 4,
 'The interaction between structural and functional connectivity may support hierarchical RL frameworks (Li et al., 2016).',
 '2025-03-08 15:55:00');

-- Insert sample data into the Comment table

INSERT INTO Comment (UserID, DocumentID, Context, CreatedAt)
VALUES
(1, 1,
 'The free-energy principle always impresses me—still one of the most unifying ideas in theoretical neuroscience.',
 NOW()),

(2, 2,
 'I love how Kanwisher combines behavioral and neuroimaging data to isolate the FFA. Classic cognitive neuroscience.',
 NOW()),

(3, 3,
 'LeCun\'s early insights into convolutional architectures really paved the way for current multimodal AI models.',
 NOW()),

(4, 4,
 'The connection between reinforcement learning and dopamine signals here is quite elegant—want to model this with real fMRI data.',
 NOW()),

(5, 5,
 'Small-world network organization in the brain seems robust across datasets. Would love to test this using resting-state data.',
 NOW()),

(6, 6,
 'Gazzaniga\'s modular brain idea is controversial but still fascinating. The independence of hemispheres raises deep questions.',
 NOW()),

(7, 7,
 'Singer\'s work always ties social cognition beautifully to neural mechanisms. Empathy has never felt so scientific.',
 NOW()),

(8, 8,
 'Dehaene\'s global workspace model is a masterpiece. I\'m curious how it scales with current LLM architectures.',
 NOW()),

(9, 9,
 'Koch\'s integration of IIT and neural correlates of consciousness is dense but foundational. A must-read.',
 NOW()),

(10, 10,
 'Sompolinsky\'s math is intense but brilliant—his population coding model makes sense of noisy neural signals.',
 NOW());

-- ========================
-- Insert into CollectionDocument
-- ========================

INSERT INTO CollectionDocument (CollectionID, DocumentID)
VALUES
-- 1. Neural Dynamics Papers: Papers on brain network dynamics and decision-making
(1, 1),
(1, 4),
(1, 5),
(1, 9),

-- 2. Vision and Perception Studies: Research on human visual perception and fMRI
(2, 2),
(2, 4),
(2, 7),

-- 3. AI and Deep Learning: Studies on deep learning architectures and neural representations
(3, 3),
(3, 5),
(3, 8),
(3, 9),

-- 4. Cognitive Control and Attention: Papers exploring neural mechanisms of attention and executive functions
(4, 1),
(4, 2),
(4, 6),

-- 5. Brain Connectivity Models: Publications on structural and functional brain connectivity modeling
(5, 5),
(5, 9),
(5, 4),

-- 6. Social Neuroscience Insights: Research on empathy, social cognition, and emotional processing
(6, 6),
(6, 7),
(6, 8),

-- 7. Consciousness and the Brain: Papers on consciousness theories and neural correlates
(7, 7),
(7, 8),
(7, 9),
(7, 10),

-- 8. Computational Neuroscience Reviews: Comprehensive reviews on computational and theoretical neuroscience
(8, 1),
(8, 4),
(8, 6),
(8, 9),

-- 9. Cognitive Psychology and AI: Studies bridging computational models and cognitive science
(9, 3),
(9, 6),
(9, 8),
(9, 10),

-- 10. Neural Representation Theory: Papers on neural information representation and modeling
(10, 3),
(10, 5),
(10, 9);

-- ========================
-- Insert into DocumentTag
-- ========================

INSERT INTO DocumentTag (DocumentID, TagID)
VALUES
-- 1. The Neural Correlates of Ambiguity and Risk in Human Decision-Making
-- Cognitive neuroscience & decision-making studies
(1, 3),  -- Cognitive Neuroscience
(1, 5),  -- Brain Connectivity
(1, 10), -- Computational Modeling

-- 2. Frontal Lobe Function and Human Decision-Making
-- fMRI-based cognitive control research
(2, 2),  -- Functional MRI
(2, 3),  -- Cognitive Neuroscience
(2, 6),  -- Visual Perception

-- 3. Learning Graph Search Heuristics
-- Machine learning and search algorithm design
(3, 4),  -- Machine Learning
(3, 8),  -- Deep Learning
(3, 10), -- Computational Modeling

-- 4. Prior Expectations Guide Multisensory Integration During Face-to-Face Communication
-- Multisensory integration and perception
(4, 3),  -- Cognitive Neuroscience
(4, 6),  -- Visual Perception
(4, 5),  -- Brain Connectivity

-- 5. Graph Neural Networks for Brain Graph Learning: A Survey
-- Neural network models for brain connectivity
(5, 1),  -- Neural Networks
(5, 5),  -- Brain Connectivity
(5, 8),  -- Deep Learning

-- 6. Decision-Making Under an Active Inference Framework
-- Theoretical neuroscience and computational modeling
(6, 3),  -- Cognitive Neuroscience
(6, 10), -- Computational Modeling
(6, 7),  -- Consciousness

-- 7. The Neural System of Metacognition Accompanying Decision Making
-- Metacognition and neural self-awareness
(7, 2),  -- Functional MRI
(7, 3),  -- Cognitive Neuroscience
(7, 7),  -- Consciousness

-- 8. Learning Admissible Heuristics for A*: Theory and Practice
-- AI learning algorithms and theoretical modeling
(8, 4),  -- Machine Learning
(8, 8),  -- Deep Learning
(8, 10), -- Computational Modeling

-- 9. BrainNetDiff: Generative AI Empowers Brain Network Generation via Multimodal Diffusion Model
-- Generative AI for brain imaging and network learning
(9, 1),  -- Neural Networks
(9, 5),  -- Brain Connectivity
(9, 8),  -- Deep Learning

-- 10. Ethical Challenges and Evolving Strategies in the Integration of AI in Healthcare and Medical Education
-- Ethics and applications of AI in neuroscience
(10, 4),  -- Machine Learning
(10, 7),  -- Consciousness
(10, 9);  -- Neuroplasticity


-- ========================
-- Insert into DocumentAuthor
-- ========================

INSERT INTO DocumentAuthor (DocumentID, AuthorID)
VALUES
-- Document 1  Shuo Zhang
(1, 1),

-- Document 2  Anne G.E. Collins
(2, 2),

-- Document 3  Márton Pándy
(3, 3),

-- Document 4  Giorgia Mazzi
(4, 4),

-- Document 5  Wei Liu, Shuo Zhang
(5, 5),
(5, 1),

-- Document 6  Shuo Zhang
(6, 1),

-- Document 7  Stephen M. Fleming
(7, 6),

-- Document 8  Ehsan Futuhi
(8, 7),

-- Document 9  Yongcheng Zong, Shuqiang Wang
(9, 8),
(9, 10),

-- Document 10  Xiaoyu Li, Emily Weiner
(10, 9),
(10, 11);