<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file AjaxNodeTreeController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\Widgets\NodeTreeWidget;

/**
 * {@inheritdoc}
 */
class AjaxNodeTreeController extends AbstractAjaxController
{
    /**
     * @param Request $request
     * @param null $translationId
     * @return JsonResponse
     */
    public function getTreeAction(Request $request, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if (null === $translationId) {
            $translation = $this->get('defaultTranslation');
        } else {
            $translation = $this->get('em')
                                ->find(
                                    '\RZ\Roadiz\Core\Entities\Translation',
                                    (int) $translationId
                                );
        }

        /** @var NodeTreeWidget|null $nodeTree */
        $nodeTree = null;

        switch ($request->get("_action")) {
            /*
             * Inner node edit for nodeTree
             */
            case 'requestNodeTree':
                if ($request->get('parentNodeId') > 0) {
                    $node = $this->get('em')
                                ->find(
                                    '\RZ\Roadiz\Core\Entities\Node',
                                    (int) $request->get('parentNodeId')
                                );
                } elseif (null !== $this->getUser()) {
                    $node = $this->getUser()->getChroot();
                } else {
                    $node = null;
                }

                $nodeTree = new NodeTreeWidget(
                    $this->getRequest(),
                    $this,
                    $node,
                    $translation
                );

                if ($request->get('tagId') &&
                    $request->get('tagId') > 0) {
                    $filterTag = $this->get('em')
                                        ->find(
                                            '\RZ\Roadiz\Core\Entities\Tag',
                                            (int) $request->get('tagId')
                                        );

                    $nodeTree->setTag($filterTag);
                }

                $this->assignation['mainNodeTree'] = false;

                if (true === (boolean) $request->get('stackTree')) {
                    $nodeTree->setStackTree(true);
                }
                break;
            /*
             * Main panel tree nodeTree
             */
            case 'requestMainNodeTree':
                $parent = null;
                if (null !== $this->getUser()) {
                    $parent = $this->getUser()->getChroot();
                }

                $nodeTree = new NodeTreeWidget(
                    $this->getRequest(),
                    $this,
                    $parent,
                    $translation
                );
                $this->assignation['mainNodeTree'] = true;
                break;
        }

        $this->assignation['nodeTree'] = $nodeTree;

        $responseArray = [
            'statusCode' => '200',
            'status' => 'success',
            'nodeTree' => $this->getTwig()->render('widgets/nodeTree/nodeTree.html.twig', $this->assignation),
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }
}
